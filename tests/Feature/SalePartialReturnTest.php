<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\Cash\CashSessionService;
use App\Services\Products\ProductWriteService;
use App\Services\Reports\SalesReportService;
use App\Services\Sales\ConfirmSaleWithPaymentService;
use App\Services\Sales\ReturnSaleItemsService;
use App\Services\Sales\SaleItemService;
use App\Services\Stocks\CancelSaleService;
use App\Services\Stocks\ConfirmSaleService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SalePartialReturnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            'create-products', 'create-sales', 'edit-sales', 'cancel-sales', 'return-sales',
            'create-stock-movements', 'open-cash', 'close-cash', 'manage-cash-movements', 'view-cash-register',
        ] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::all());
        $this->actingAs($user);
    }

    private function product(int $stock = 10): Product
    {
        return app(ProductWriteService::class)->save([
            'name' => 'Produto teste',
            'slug' => (string) Str::uuid(),
            'price' => 10,
            'stock' => $stock,
            'is_active' => true,
            'is_on_sale' => false,
        ]);
    }

    private function openCash(): void
    {
        app(CashSessionService::class)->open(100);
    }

    private function sale(Product $product, int $quantity = 2): Sale
    {
        $sale = Sale::create(['user_id' => auth()->id()]);
        app(SaleItemService::class)->add($sale, $product->id, $quantity);

        return $sale->refresh();
    }

    private function rejects(callable $callback, string $exception = \DomainException::class): void
    {
        try {
            $callback();
        } catch (\Throwable $error) {
            $this->assertInstanceOf($exception, $error);

            return;
        }
        $this->fail('A operação deveria ser recusada.');
    }

    public function test_partial_restock_refund_keeps_sale_confirmed_and_reduces_revenue(): void
    {
        $this->openCash();
        $product = $this->product();
        $sale = $this->sale($product, 2);
        app(ConfirmSaleWithPaymentService::class)->confirm($sale, [
            ['method' => 'pix', 'amount' => 20],
        ]);

        $item = $sale->items()->firstOrFail();
        app(ReturnSaleItemsService::class)->returnItems(
            $sale,
            [['sale_item_id' => $item->id, 'quantity' => 1]],
            SaleReturn::DESTINATION_RESTOCK,
            'Cliente devolveu uma unidade',
            ['method' => 'pix', 'amount' => 10],
        );

        $sale->refresh()->load(['payments', 'returns.items']);
        $this->assertSame('confirmed', $sale->status);
        $this->assertSame(Sale::PAYMENT_PAID, $sale->payment_status);
        $this->assertSame(1000, $sale->amountPaidCents());
        $this->assertSame(9, (int) $product->fresh()->stock);
        $this->assertSame(1, $sale->returns()->count());
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'reason' => 'return',
            'quantity' => 1,
        ]);

        $report = app(SalesReportService::class)->build([
            'status' => 'confirmed',
            'payment_status' => Sale::PAYMENT_PAID,
        ], 50);
        $this->assertSame(1, $report['totals']['sales']);
        $this->assertSame(10.0, $report['totals']['revenue']);
    }

    public function test_loss_destination_does_not_increase_stock(): void
    {
        $this->openCash();
        $product = $this->product();
        $sale = $this->sale($product, 2);
        app(ConfirmSaleWithPaymentService::class)->confirm($sale, [
            ['method' => 'cash', 'amount' => 20, 'amount_tendered' => 20],
        ]);
        $stockAfterSale = (int) $product->fresh()->stock;

        $item = $sale->items()->firstOrFail();
        app(ReturnSaleItemsService::class)->returnItems(
            $sale,
            [['sale_item_id' => $item->id, 'quantity' => 1]],
            SaleReturn::DESTINATION_LOSS,
            'Produto avariado',
            ['method' => 'cash', 'amount' => 10],
        );

        $this->assertSame($stockAfterSale, (int) $product->fresh()->stock);
        $this->assertDatabaseHas('sale_returns', [
            'sale_id' => $sale->id,
            'destination' => SaleReturn::DESTINATION_LOSS,
        ]);
    }

    public function test_full_return_with_full_refund_cancels_sale(): void
    {
        $this->openCash();
        $product = $this->product();
        $sale = $this->sale($product, 2);
        app(ConfirmSaleWithPaymentService::class)->confirm($sale, [
            ['method' => 'pix', 'amount' => 20],
        ]);
        $item = $sale->items()->firstOrFail();

        app(ReturnSaleItemsService::class)->returnItems(
            $sale,
            [['sale_item_id' => $item->id, 'quantity' => 2]],
            SaleReturn::DESTINATION_RESTOCK,
            'Desistência total',
            ['method' => 'pix', 'amount' => 20],
        );

        $sale->refresh();
        $this->assertSame('canceled', $sale->status);
        $this->assertSame(Sale::PAYMENT_REFUNDED, $sale->payment_status);
        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->rejects(fn () => app(ReturnSaleItemsService::class)->returnItems(
            $sale,
            [['sale_item_id' => $item->id, 'quantity' => 1]],
            SaleReturn::DESTINATION_RESTOCK,
            'Repetição',
            ['method' => 'pix', 'amount' => 0],
        ));
    }

    public function test_unpaid_return_restocks_without_refund_and_cancels_when_complete(): void
    {
        $product = $this->product();
        $sale = $this->sale($product, 2);
        app(ConfirmSaleService::class)->confirm($sale);
        $item = $sale->items()->firstOrFail();

        app(ReturnSaleItemsService::class)->returnItems(
            $sale,
            [['sale_item_id' => $item->id, 'quantity' => 1]],
            SaleReturn::DESTINATION_RESTOCK,
            'Pedido chat parcial',
        );
        $this->assertSame('confirmed', $sale->fresh()->status);
        $this->assertSame(Sale::PAYMENT_UNPAID, $sale->fresh()->payment_status);
        $this->assertSame(9, (int) $product->fresh()->stock);

        app(ReturnSaleItemsService::class)->returnItems(
            $sale,
            [['sale_item_id' => $item->id, 'quantity' => 1]],
            SaleReturn::DESTINATION_RESTOCK,
            'Pedido chat restante',
        );
        $this->assertSame('canceled', $sale->fresh()->status);
        $this->assertSame(10, (int) $product->fresh()->stock);
    }

    public function test_rejects_quantity_above_remaining_and_unauthorized_user(): void
    {
        $this->openCash();
        $product = $this->product();
        $sale = $this->sale($product, 2);
        app(ConfirmSaleWithPaymentService::class)->confirm($sale, [
            ['method' => 'other', 'amount' => 20],
        ]);
        $item = $sale->items()->firstOrFail();

        $this->rejects(fn () => app(ReturnSaleItemsService::class)->returnItems(
            $sale,
            [['sale_item_id' => $item->id, 'quantity' => 3]],
            SaleReturn::DESTINATION_RESTOCK,
            'Acima do residual',
            ['method' => 'other', 'amount' => 0],
        ));

        $other = User::factory()->create();
        $this->actingAs($other);
        $this->rejects(
            fn () => app(ReturnSaleItemsService::class)->returnItems(
                $sale,
                [['sale_item_id' => $item->id, 'quantity' => 1]],
                SaleReturn::DESTINATION_RESTOCK,
                'Sem permissão',
                ['method' => 'other', 'amount' => 0],
            ),
            AuthorizationException::class,
        );
    }

    public function test_cancel_after_partial_refund_only_refunds_remaining_net(): void
    {
        $this->openCash();
        $product = $this->product();
        $sale = $this->sale($product, 2);
        app(ConfirmSaleWithPaymentService::class)->confirm($sale, [
            ['method' => 'pix', 'amount' => 20],
        ]);
        $item = $sale->items()->firstOrFail();

        app(ReturnSaleItemsService::class)->returnItems(
            $sale,
            [['sale_item_id' => $item->id, 'quantity' => 1]],
            SaleReturn::DESTINATION_RESTOCK,
            'Parcial',
            ['method' => 'pix', 'amount' => 10],
        );

        app(CancelSaleService::class)->cancel($sale, 'Cancela o restante');
        $sale->refresh()->load('payments');
        $this->assertSame('canceled', $sale->status);
        $this->assertSame(Sale::PAYMENT_REFUNDED, $sale->payment_status);
        $this->assertSame(0, $sale->amountPaidCents());
        $this->assertSame(20.0, (float) $sale->payments->where('type', 'refund')->sum('amount'));
        $this->assertSame(10, (int) $product->fresh()->stock);
    }
}
