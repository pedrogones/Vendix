<?php

namespace Tests\Concurrency;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Products\ProductWriteService;
use App\Services\Sales\ChatbotCheckoutService;
use App\Services\Sales\SaleItemService;
use App\Services\Stocks\CancelSaleService;
use App\Services\Stocks\ConfirmSaleService;
use App\Services\Stocks\StockService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

// Run explicitly against a disposable MySQL database; excluded from the regular SQLite suite.
class SalesConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        if (getenv('VENDIX_CONCURRENCY_TESTS') !== '1') {
            $this->markTestSkipped('Requires an explicitly authorized disposable MySQL database.');
        }
        parent::setUp();
        $this->assertSame('mysql', DB::getDriverName());
        $this->assertSame('vendix_integrity_test', DB::getDatabaseName(), 'This suite must never run against an operational database.');
        $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
        foreach (['create-products', 'create-sales', 'create-stock-movements', 'cancel-sales'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::all());
        $this->actingAs($user);
        config(['cache.default' => 'database']);
    }

    private function product(int $stock = 10): Product
    {
        return app(ProductWriteService::class)->save(['name' => 'Concorrência', 'slug' => 'race', 'price' => 10, 'stock' => $stock, 'is_active' => true]);
    }

    private function sale(Product $product): Sale
    {
        $sale = Sale::create(['user_id' => auth()->id()]);
        app(SaleItemService::class)->add($sale, $product->id, 2);

        return $sale;
    }

    private function race(callable $callback): array
    {
        $prefix = tempnam(sys_get_temp_dir(), 'vendix-race-');
        $children = [];
        // Do not share PDO sockets across forked processes.
        DB::disconnect();
        for ($index = 0; $index < 2; $index++) {
            $pid = pcntl_fork();
            if ($pid === -1) {
                $this->fail('Unable to fork a test worker.');
            }
            if ($pid === 0) {
                DB::purge();
                file_put_contents($prefix.'.'.$index.'.ready', 'ready');
                $deadline = microtime(true) + 10;
                while (! file_exists($prefix.'.go') && microtime(true) < $deadline) {
                    usleep(1000);
                }
                try {
                    $callback($index);
                    $result = 'ok';
                } catch (\DomainException $exception) {
                    $result = 'domain';
                } catch (\Throwable $exception) {
                    $result = get_class($exception).': '.$exception->getMessage();
                }
                file_put_contents($prefix.'.'.$index.'.result', $result);
                exit(0);
            }
            $children[] = $pid;
        }
        $deadline = microtime(true) + 10;
        while ((! file_exists($prefix.'.0.ready') || ! file_exists($prefix.'.1.ready')) && microtime(true) < $deadline) {
            usleep(1000);
        }
        touch($prefix.'.go');
        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);
            $this->assertSame(0, pcntl_wexitstatus($status));
        }
        DB::purge();
        $results = [file_get_contents($prefix.'.0.result'), file_get_contents($prefix.'.1.result')];
        foreach (glob($prefix.'*') as $file) {
            unlink($file);
        }

        return $results;
    }

    public function test_simultaneous_confirmation_moves_stock_once(): void
    {
        $product = $this->product();
        $sale = $this->sale($product);
        $results = $this->race(fn () => app(ConfirmSaleService::class)->confirm(Sale::findOrFail($sale->id)));
        sort($results);
        $this->assertSame(['domain', 'ok'], $results);
        $this->assertSame(8, (int) $product->fresh()->stock);
        $this->assertSame(1, StockMovement::where('reason', 'sale')->count());
    }

    public function test_simultaneous_cancellation_restores_stock_once(): void
    {
        $product = $this->product();
        $sale = $this->sale($product);
        app(ConfirmSaleService::class)->confirm($sale);
        $results = $this->race(fn () => app(CancelSaleService::class)->cancel(Sale::findOrFail($sale->id), 'Cancelamento concorrente'));
        sort($results);
        $this->assertSame(['domain', 'ok'], $results);
        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(1, StockMovement::where('reason', 'return')->count());
    }

    public function test_simultaneous_checkout_returns_same_sale(): void
    {
        $product = $this->product();
        app(CartService::class)->addItem('concurrent-checkout', $product, 2, []);
        $results = $this->race(fn () => app(ChatbotCheckoutService::class)->finalize('concurrent-checkout'));
        $this->assertSame(['ok', 'ok'], $results);
        $this->assertSame(1, Sale::count());
        $this->assertSame(8, (int) $product->fresh()->stock);
        $this->assertSame(1, StockMovement::where('reason', 'sale')->count());
    }

    public function test_different_sales_cannot_oversell_the_same_product(): void
    {
        $product = $this->product(3);
        $this->sale($product);
        $this->sale($product);
        $results = $this->race(function (int $index): void {
            $sale = Sale::orderBy('id')->skip($index)->firstOrFail();
            app(ConfirmSaleService::class)->confirm($sale);
        });
        sort($results);
        $this->assertSame(['domain', 'ok'], $results);
        $this->assertSame(1, (int) $product->fresh()->stock);
        $this->assertSame(1, Sale::where('status', 'confirmed')->count());
    }

    public function test_simultaneous_manual_compensation_runs_once(): void
    {
        $product = $this->product();
        $movement = app(StockService::class)->manual($product, 2, 'out', 'loss', 'Perda');
        $results = $this->race(fn () => app(StockService::class)->compensate($movement, 'Correção concorrente'));
        sort($results);
        $this->assertSame(['domain', 'ok'], $results);
        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(1, StockMovement::where('compensates_id', $movement->id)->count());
    }

    public function test_integrity_migration_round_trip_preserves_mysql_data(): void
    {
        $product = $this->product();
        $migration = require database_path('migrations/2026_10_21_000001_add_sale_integrity_fields.php');
        $migration->down();
        $migration->up();
        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(1, StockMovement::count());
    }
}
