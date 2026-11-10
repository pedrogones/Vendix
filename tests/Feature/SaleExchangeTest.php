<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\Cash\CashSessionService;
use App\Services\Products\ProductWriteService;
use App\Services\Sales\ConfirmSaleWithPaymentService;
use App\Services\Sales\ExchangeSaleItemsService;
use App\Services\Sales\SaleItemService;
use App\Services\Stocks\ConfirmSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SaleExchangeTest extends TestCase
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

    private function product(float $price = 10, int $stock = 10): Product
    {
        return app(ProductWriteService::class)->save([
            'name' => 'Produto '.$price,
            'slug' => (string) Str::uuid(),
            'price' => $price,
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

    private function rejects(callable $callback): void
    {
        try {
            $callback();
        } catch (\DomainException) {
            return;
        }
        $this->fail('A operação deveria ser recusada.');
    }

    public function test_equal_value_exchange_moves_stock_without_settlement(): void
    {
        $this->openCash();
        $original = $this->product(10, 10);
        $replacement = $this->product(10, 5);
        $sale = $this->sale($original, 2);
        app(ConfirmSaleWithPaymentService::class)->confirm($sale, [
            ['method' => 'pix', 'amount' => 20],
        ]);

        $item = $sale->items()->firstOrFail();
        app(ExchangeSaleItemsService::class)->exchange(
            $sale,
            [['sale_item_id' => $item->id, 'quantity' => 1]],
            [['product_id' => $replacement->id, 'quantity' => 1]],
            SaleReturn::DESTINATION_RESTOCK,
            'Troca mesmo valor',
            ['method' => 'pix', 'amount' => 0],
        );

        $sale->refresh()->load(['exchanges', 'payments', 'returns']);
        $this->assertSame('confirmed', $sale->status);
        $this->assertSame(Sale::PAYMENT_PAID, $sale->payment_status);
        $this->assertSame(1, $sale->exchanges()->count());
        $this->assertSame(9, (int) $original->fresh()->stock); // 10-2+1
        $this->assertSame(4, (int) $replacement->fresh()->stock); // 5-1
        $this->assertSame(0, $sale->exchanges()->first()->settlement_cents);
        $this->assertSame(0, $sale->payments()->where('type', 'refund')->count());
    }

    public function test_more_expensive_replacement_requires_additional_payment(): void
    {
        $this->openCash();
        $original = $this->product(10, 10);
        $replacement = $this->product(15, 5);
        $sale = $this->sale($original, 1);
        app(ConfirmSaleWithPaymentService::class)->confirm($sale, [
            ['method' => 'pix', 'amount' => 10],
        ]);
        $item = $sale->items()->firstOrFail();

        $this->rejects(fn () => app(ExchangeSaleItemsService::class)->exchange(
            $sale,
            [['sale_item_id' => $item->id, 'quantity' => 1]],
            [['product_id' => $replacement->id, 'quantity' => 1]],
            SaleReturn::DESTINATION_RESTOCK,
            'Troca cara',
            ['method' => 'pix', 'amount' => 0],
        ));

        app(ExchangeSaleItemsService::class)->exchange(
            $sale,
            [['sale_item_id' => $item->id, 'quantity' => 1]],
            [['product_id' => $replacement->id, 'quantity' => 1]],
            SaleReturn::DESTINATION_RESTOCK,
            'Troca cara',
            ['method' => 'pix', 'amount' => 5],
        );

        $sale->refresh()->load('payments');
        $this->assertSame('confirmed', $sale->status);
        $this->assertSame(1500, $sale->amountPaidCents());
        $this->assertDatabaseHas('sale_payments', [
            'sale_id' => $sale->id,
            'type' => 'payment',
            'amount' => 5,
            'notes' => 'Acerto de troca (+): Troca cara',
        ]);
    }

    public function test_cheaper_replacement_refunds_difference(): void
    {
        $this->openCash();
        $original = $this->product(20, 10);
        $replacement = $this->product(12, 5);
        $sale = $this->sale($original, 1);
        app(ConfirmSaleWithPaymentService::class)->confirm($sale, [
            ['method' => 'card', 'amount' => 20],
        ]);
        $item = $sale->items()->firstOrFail();

        app(ExchangeSaleItemsService::class)->exchange(
            $sale,
            [['sale_item_id' => $item->id, 'quantity' => 1]],
            [['product_id' => $replacement->id, 'quantity' => 1]],
            SaleReturn::DESTINATION_RESTOCK,
            'Troca barata',
            ['method' => 'card', 'amount' => 8],
        );

        $sale->refresh();
        $this->assertSame(1200, $sale->amountPaidCents());
        $this->assertDatabaseHas('sale_payments', [
            'sale_id' => $sale->id,
            'type' => 'refund',
            'amount' => 8,
        ]);
    }

    public function test_unpaid_exchange_only_moves_stock_and_does_not_cancel(): void
    {
        $original = $this->product(10, 10);
        $replacement = $this->product(10, 5);
        $sale = $this->sale($original, 2);
        app(ConfirmSaleService::class)->confirm($sale);
        $item = $sale->items()->firstOrFail();

        app(ExchangeSaleItemsService::class)->exchange(
            $sale,
            [['sale_item_id' => $item->id, 'quantity' => 2]],
            [['product_id' => $replacement->id, 'quantity' => 1]],
            SaleReturn::DESTINATION_RESTOCK,
            'Troca pedido chat',
        );

        $sale->refresh();
        $this->assertSame('confirmed', $sale->status);
        $this->assertSame(Sale::PAYMENT_UNPAID, $sale->payment_status);
        $this->assertTrue($sale->allItemsFullyReturned());
        $this->assertSame(10, (int) $original->fresh()->stock);
        $this->assertSame(4, (int) $replacement->fresh()->stock);
    }
}
