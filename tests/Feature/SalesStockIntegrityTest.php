<?php

namespace Tests\Feature;

use App\Exceptions\CartPriceChangedException;
use App\Filament\Resources\Sales\SaleResource;
use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Chatbot\ProductRuleService;
use App\Services\Products\ProductWriteService;
use App\Services\Sales\ChatbotCheckoutService;
use App\Services\Sales\SaleItemService;
use App\Services\Stocks\CancelSaleService;
use App\Services\Stocks\ConfirmSaleService;
use App\Services\Stocks\StockService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SalesStockIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['view-dashboard', 'create-products', 'edit-products', 'create-sales', 'edit-sales', 'delete-sales', 'cancel-sales', 'view-stock-movements', 'view-any-stock-movements', 'create-stock-movements', 'view-products', 'view-any-products', 'view-sales', 'view-any-sales'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::all());
        $this->actingAs($user);
    }

    private function product(int $stock = 10, array $attributes = []): Product
    {
        return app(ProductWriteService::class)->save(array_merge([
            'name' => 'Produto teste', 'slug' => (string) Str::uuid(), 'price' => 10,
            'stock' => $stock, 'is_active' => true, 'is_on_sale' => false,
        ], $attributes));
    }

    private function sale(Product $product, int $quantity = 2): Sale
    {
        $sale = Sale::create(['user_id' => auth()->id()]);
        app(SaleItemService::class)->add($sale, $product->id, $quantity);

        return $sale->refresh();
    }

    private function cart(Product $product, int $quantity = 2, string $session = 'test-session'): array
    {
        return app(CartService::class)->addItem($session, $product, $quantity, []);
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

    public static function promotionCases(): array
    {
        return [[true, 8, 8.0], [false, 8, 10.0], [true, null, 10.0], [true, 0, 10.0], [true, 12, 10.0]];
    }

    #[DataProvider('promotionCases')]
    public function test_promotion_rule_is_shared(bool $active, ?float $promotional, float $expected): void
    {
        $product = new Product(['price' => 10, 'is_on_sale' => $active, 'promotional_price' => $promotional]);
        $this->assertSame($expected, $product->final_price);
        $this->assertSame($expected, app(ProductRuleService::class)->pricing($product)['unit_price']);
    }

    public function test_opening_and_product_edit_create_auditable_movements(): void
    {
        $product = $this->product(10, ['description' => 'Descrição persistida']);
        $this->assertSame('Descrição persistida', $product->description);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'in', 'quantity' => 10, 'user_id' => auth()->id()]);
        $updated = app(ProductWriteService::class)->save(['price' => 10, 'stock' => 7, 'stock_reason' => 'Contagem física'], $product);
        $this->assertSame(7, (int) $updated->stock);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'out', 'quantity' => 3, 'reference' => 'Contagem física']);
    }

    public function test_direct_stock_edit_and_unexplained_edit_are_rejected(): void
    {
        $product = $this->product();
        $this->rejects(fn () => $product->update(['stock' => 20]));
        $product->refresh();
        $this->rejects(fn () => app(ProductWriteService::class)->save(['price' => 10, 'stock' => 20], $product), ValidationException::class);
        $this->assertSame(10, (int) $product->fresh()->stock);
    }

    public function test_confirm_cancel_and_stale_retries_do_not_repeat_effects(): void
    {
        $product = $this->product();
        $sale = $this->sale($product);
        $stale = Sale::findOrFail($sale->id);
        app(ConfirmSaleService::class)->confirm($sale);
        $this->assertSame(8, (int) $product->fresh()->stock);
        $this->rejects(fn () => app(ConfirmSaleService::class)->confirm($stale));
        $this->assertSame(1, StockMovement::where('reason', 'sale')->count());
        $stale = Sale::findOrFail($sale->id);
        app(CancelSaleService::class)->cancel($sale, 'Cliente desistiu');
        $this->rejects(fn () => app(CancelSaleService::class)->cancel($stale, 'Repetição'));
        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'canceled', 'canceled_by' => auth()->id(), 'cancellation_reason' => 'Cliente desistiu']);
        $this->assertDatabaseHas('stock_movements', ['reference_id' => $sale->id, 'type' => 'in', 'reason' => 'return', 'quantity' => 2]);
    }

    public function test_confirmation_rolls_back_all_items_when_stock_runs_out(): void
    {
        $first = $this->product();
        $second = $this->product();
        $sale = $this->sale($first);
        app(SaleItemService::class)->add($sale, $second->id, 2);
        app(StockService::class)->manual($second, 9, 'out', 'loss', 'Perda após rascunho');
        $this->rejects(fn () => app(ConfirmSaleService::class)->confirm($sale));
        $this->assertSame(10, (int) $first->fresh()->stock);
        $this->assertSame('draft', $sale->fresh()->status);
        $this->assertSame(0, StockMovement::where('reason', 'sale')->count());
    }

    public function test_empty_and_inactive_sales_are_rejected(): void
    {
        $empty = Sale::create(['user_id' => auth()->id()]);
        $this->rejects(fn () => app(ConfirmSaleService::class)->confirm($empty));
        $product = $this->product();
        $sale = $this->sale($product);
        $product->update(['is_active' => false]);
        $this->rejects(fn () => app(ConfirmSaleService::class)->confirm($sale));
        $this->assertSame(10, (int) $product->fresh()->stock);
    }

    public function test_items_and_deletion_are_blocked_after_confirmation_even_with_stale_models(): void
    {
        $product = $this->product();
        $sale = $this->sale($product);
        $stale = Sale::findOrFail($sale->id);
        $item = $sale->items()->firstOrFail();
        app(ConfirmSaleService::class)->confirm($sale);
        $this->rejects(fn () => $item->update(['quantity' => 9]));
        $this->rejects(fn () => $item->delete());
        $this->rejects(fn () => $stale->delete());
        $this->rejects(fn () => $stale->forceDelete());
        $this->rejects(fn () => $stale->update(['total' => 0]));
        $this->rejects(fn () => app(SaleItemService::class)->add($stale, $product->id, 1));
        $this->assertFalse(SaleResource::canDelete($sale));
        $this->assertSame(8, (int) $product->fresh()->stock);
    }

    public function test_draft_items_recalculate_totals_and_reject_excess_discount_and_quantity(): void
    {
        $product = $this->product();
        $sale = $this->sale($product);
        $item = $sale->items()->firstOrFail();
        app(SaleItemService::class)->edit($sale, $item, 3, 2);
        $this->assertSame('28.00', $sale->fresh()->total);
        $this->rejects(fn () => app(SaleItemService::class)->add($sale, $product->id, 8));
        $this->rejects(fn () => app(SaleItemService::class)->edit($sale, $item, 1, 11));
        app(SaleItemService::class)->remove($sale, $item->id);
        $this->assertSame('0.00', $sale->fresh()->total);
        $sale->delete();
        $this->assertSoftDeleted($sale);
    }

    public function test_draft_preserves_captured_price_after_catalog_change(): void
    {
        $product = $this->product();
        $sale = $this->sale($product);
        $product->update(['price' => 20]);
        app(ConfirmSaleService::class)->confirm($sale);
        $this->assertSame('20.00', $sale->total);
    }

    public function test_manual_movements_count_and_compensation_preserve_history(): void
    {
        $product = $this->product();
        $movement = app(StockService::class)->manual($product, 3, 'out', 'loss', 'Quebra');
        $this->assertSame(7, (int) $product->fresh()->stock);
        $this->rejects(fn () => $movement->update(['quantity' => 1]));
        $this->rejects(fn () => $movement->delete());
        $correction = app(StockService::class)->compensate($movement, 'Lançamento incorreto');
        $this->assertSame($movement->id, $correction->compensates_id);
        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->rejects(fn () => app(StockService::class)->compensate($movement, 'Repetição'));
        $this->rejects(fn () => app(StockService::class)->compensate($correction, 'Reversão de reversão'));
        $count = StockMovement::count();
        $this->assertNull(app(StockService::class)->count($product, 10, 'Conferência'));
        $this->assertSame($count, StockMovement::count());
        app(StockService::class)->count($product, 4, 'Inventário');
        $this->assertSame(4, (int) $product->fresh()->stock);
        $this->assertFalse(StockMovementResource::canDelete($movement));
    }

    public function test_sale_movements_cannot_be_compensated_manually(): void
    {
        $sale = $this->sale($this->product());
        app(ConfirmSaleService::class)->confirm($sale);
        $movement = StockMovement::where('reason', 'sale')->firstOrFail();
        $this->rejects(fn () => app(StockService::class)->compensate($movement, 'Atalho'));
    }

    public function test_chatbot_uses_same_confirmation_and_persisted_key_for_retries(): void
    {
        $product = $this->product(10, ['is_on_sale' => true, 'promotional_price' => 8]);
        $cart = $this->cart($product);
        $order = app(ChatbotCheckoutService::class)->finalize('test-session');
        $this->assertSame(16.0, $order['total']);
        $this->assertSame(8, (int) $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['reason' => 'sale', 'quantity' => 2, 'reference_id' => $order['sale_id']]);
        $this->assertDatabaseHas('sale_items', ['sale_id' => $order['sale_id'], 'unit_price' => 8, 'discount' => 0, 'total_price' => 16]);
        $this->assertSame($order, app(ChatbotCheckoutService::class)->finalize('test-session'));
        // Simulate a process failure after commit, before cache completion.
        Cache::put('chatbot:cart:test-session', $cart, 60);
        $this->assertSame($order, app(ChatbotCheckoutService::class)->finalize('test-session'));
        $this->assertSame(1, Sale::count());
        $this->assertSame(1, StockMovement::where('reason', 'sale')->count());
        $this->cart($product, 1);
        $next = app(ChatbotCheckoutService::class)->finalize('test-session');
        $this->assertNotSame($order['sale_id'], $next['sale_id']);
    }

    public function test_chatbot_price_change_requires_new_confirmation_and_rolls_back_sale(): void
    {
        $product = $this->product();
        $cart = $this->cart($product);
        $product->update(['price' => 12]);
        $this->rejects(fn () => app(ChatbotCheckoutService::class)->finalize('test-session'), CartPriceChangedException::class);
        $this->assertSame(0, Sale::count());
        $this->assertSame(10, (int) $product->fresh()->stock);
        $updated = app(CartService::class)->get('test-session');
        $this->assertSame(24.0, $updated['subtotal']);
        $this->assertNotSame($cart['checkout_key'], $updated['checkout_key']);
        $this->assertSame(24.0, app(ChatbotCheckoutService::class)->finalize('test-session')['total']);
    }

    public function test_chatbot_failure_preserves_cart_without_partial_effects(): void
    {
        $product = $this->product();
        $this->cart($product);
        app(StockService::class)->count($product, 1, 'Inventário');
        $this->rejects(fn () => app(ChatbotCheckoutService::class)->finalize('test-session'));
        $this->assertSame(0, Sale::count());
        $this->assertSame(2, app(CartService::class)->get('test-session')['total_items']);
        $this->assertSame(0, StockMovement::where('reason', 'sale')->count());
    }

    public function test_unauthorized_user_cannot_adjust_cancel_or_change_another_users_sale(): void
    {
        $product = $this->product();
        $sale = $this->sale($product);
        app(ConfirmSaleService::class)->confirm($sale);
        $otherDraft = Sale::create(['user_id' => auth()->id()]);
        $this->actingAs(User::factory()->create());
        $this->rejects(fn () => app(StockService::class)->manual($product, 1, 'in', 'manual', 'Tentativa'), AuthorizationException::class);
        $this->rejects(fn () => app(StockService::class)->count($product, 100, 'Tentativa'), AuthorizationException::class);
        $this->rejects(fn () => app(CancelSaleService::class)->cancel($sale, 'Tentativa'), AuthorizationException::class);
        $this->rejects(fn () => app(ProductWriteService::class)->save(['price' => 10, 'stock' => 20, 'stock_reason' => 'Tentativa'], $product), AuthorizationException::class);
        $this->rejects(fn () => app(SaleItemService::class)->add($otherDraft, $product->id, 1), AuthorizationException::class);
        $this->assertSame(8, (int) $product->fresh()->stock);
    }

    public function test_invalid_manual_moves_and_compensation_without_stock_are_rejected(): void
    {
        $product = $this->product(0);
        $this->rejects(fn () => app(StockService::class)->manual($product, 0, 'in', 'manual', 'Inválido'));
        $this->rejects(fn () => app(StockService::class)->manual($product, 1, 'adjustment', 'manual', 'Inválido'));
        $this->rejects(fn () => app(StockService::class)->count($product, -1, 'Inválido'));
        $entry = app(StockService::class)->manual($product, 3, 'in', 'purchase', 'Compra');
        app(StockService::class)->manual($product, 3, 'out', 'loss', 'Quebra');
        $this->rejects(fn () => app(StockService::class)->compensate($entry, 'Não pode negativar'));
        $this->assertSame(0, (int) $product->fresh()->stock);
    }

    public function test_vitrine_only_lists_active_valid_promotions(): void
    {
        $valid = $this->product(10, ['is_on_sale' => true, 'promotional_price' => 8]);
        $disabled = $this->product(10, ['is_on_sale' => false, 'promotional_price' => 7]);
        $this->assertSame([$valid->id], Product::onPromotion()->pluck('id')->all());
        $this->assertSame([$valid->id], app(ProductRuleService::class)->promotionalProducts()->pluck('id')->all());
        $this->get('/vendix-preview')->assertOk()->assertViewHas('promotionalProducts', fn ($products) => $products->pluck('id')->all() === [$valid->id]);
    }

    public function test_panel_resources_load_after_integrity_changes(): void
    {
        $this->get('/admin/products')->assertOk();
        $this->get('/admin/sales')->assertOk();
        $this->get('/admin/stock-movements')->assertOk();
    }

    public function test_product_creation_and_scanner_edit_use_stock_services(): void
    {
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        \Livewire\Livewire::test(\App\Filament\Resources\Products\Pages\ManageProducts::class)
            ->callAction('create', data: [
                'name' => 'Produto por tela', 'slug' => 'produto-por-tela', 'description' => 'Descrição',
                'price' => 10, 'stock' => 5, 'min_stock' => 1, 'is_active' => true,
            ])->assertHasNoActionErrors();
        $product = Product::where('slug', 'produto-por-tela')->firstOrFail();
        $this->assertSame(5, (int) $product->stock);
        \Livewire\Livewire::test(\App\Filament\Resources\Products\Pages\ManageProducts::class)
            ->mountAction('editFromScanner', arguments: ['product_id' => $product->id])
            ->fillForm(['stock' => 3, 'stock_reason' => 'Contagem via scanner', 'description' => 'Editado pelo scanner'])
            ->callMountedAction()
            ->assertHasNoActionErrors();
        $this->assertSame(3, (int) $product->fresh()->stock);
        $this->assertSame('Editado pelo scanner', $product->fresh()->description);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'quantity' => 2, 'type' => 'out', 'reference' => 'Contagem via scanner']);
    }

    public function test_manual_stock_and_cancellation_actions_work_in_panel(): void
    {
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        $product = $this->product();
        \Livewire\Livewire::test(\App\Filament\Resources\StockMovements\Pages\ManageStockMovements::class)
            ->callAction('create', data: ['product_id' => $product->id, 'quantity' => 2, 'type' => 'in', 'reason' => 'purchase', 'reference' => 'Entrada pela tela'])
            ->assertHasNoActionErrors();
        $this->assertSame(12, (int) $product->fresh()->stock);
        $sale = $this->sale($product);
        app(ConfirmSaleService::class)->confirm($sale);
        \Livewire\Livewire::test(\App\Filament\Resources\Sales\Pages\ManageSales::class)
            ->callTableAction('cancel', $sale, data: ['reason' => 'Cancelamento pela tela'])
            ->assertHasNoActionErrors();
        $this->assertSame('canceled', $sale->fresh()->status);
        $this->assertSame(12, (int) $product->fresh()->stock);
    }

    public function test_anonymous_chatbot_records_stock_without_requiring_staff_permission(): void
    {
        $product = $this->product();
        $this->cart($product);
        auth()->logout();
        $order = app(ChatbotCheckoutService::class)->finalize('test-session');
        $this->assertDatabaseHas('stock_movements', ['reference_id' => $order['sale_id'], 'reason' => 'sale', 'user_id' => null]);
    }

    public function test_permission_sync_does_not_reset_existing_admin_password(): void
    {
        $user = auth()->user();
        $password = $user->password;
        $this->seed(\Database\Seeders\SalesStockPermissionsSeeder::class);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertTrue(\Spatie\Permission\Models\Role::findByName('Admin', 'web')->hasPermissionTo('cancel-sales'));
    }

    public function test_regular_product_edit_and_stock_correction_actions(): void
    {
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        $product = $this->product();
        \Livewire\Livewire::test(\App\Filament\Resources\Products\Pages\ManageProducts::class)
            ->callTableAction('edit', $product, data: ['stock' => 8, 'stock_reason' => 'Contagem pela edição'])
            ->assertHasNoActionErrors();
        $this->assertSame(8, (int) $product->fresh()->stock);
        $movement = StockMovement::where('reference', 'Contagem pela edição')->firstOrFail();
        \Livewire\Livewire::test(\App\Filament\Resources\StockMovements\Pages\ManageStockMovements::class)
            ->callTableAction('compensate', $movement, data: ['description' => 'Correção pela tela'])
            ->assertHasNoActionErrors();
        $this->assertSame(10, (int) $product->fresh()->stock);
        \Livewire\Livewire::test(\App\Filament\Resources\StockMovements\Pages\ManageStockMovements::class)
            ->callAction('count', data: ['product_id' => $product->id, 'balance' => 6, 'description' => 'Inventário pela tela'])
            ->assertHasNoActionErrors();
        $this->assertSame(6, (int) $product->fresh()->stock);
    }

    public function test_balcao_add_remove_confirm_flow(): void
    {
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        $product = $this->product();
        $screen = \Livewire\Livewire::test(\App\Filament\Pages\StartSale::class)
            ->fillForm(['product_id' => $product->id, 'quantity' => 2, 'discount' => 1], 'saleForm')
            ->call('addItem')->assertHasNoErrors();
        $sale = $screen->instance()->sale;
        $this->assertSame('19.00', $sale->fresh()->total);
        $screen->call('removeItem', $sale->items()->firstOrFail()->id);
        $this->assertSame('0.00', $sale->fresh()->total);
        $screen->fillForm(['product_id' => $product->id, 'quantity' => 2, 'discount' => 0], 'saleForm')
            ->call('addItem')->call('confirmSale')->assertHasNoErrors();
        $this->assertSame('confirmed', $sale->fresh()->status);
        $this->assertSame(8, (int) $product->fresh()->stock);
    }

    public function test_bulk_delete_preserves_confirmed_and_canceled_sales(): void
    {
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        $product = $this->product();
        $confirmed = $this->sale($product);
        $canceled = $this->sale($product);
        $draft = $this->sale($product);
        app(ConfirmSaleService::class)->confirm($confirmed);
        app(ConfirmSaleService::class)->confirm($canceled);
        app(CancelSaleService::class)->cancel($canceled, 'Cancelamento');
        \Livewire\Livewire::test(\App\Filament\Resources\Sales\Pages\ManageSales::class)
            ->callTableBulkAction('delete', [$confirmed, $canceled, $draft]);
        $this->assertNotSoftDeleted($confirmed);
        $this->assertNotSoftDeleted($canceled);
        $this->assertSoftDeleted($draft);
        $this->assertSame(8, (int) $product->fresh()->stock);
    }

    public function test_chatbot_keeps_confirmation_step_when_price_changes(): void
    {
        $product = $this->product();
        $this->cart($product);
        app(\App\Services\Chatbot\ConversationStateService::class)->update('test-session', ['step' => 'awaiting_confirmation']);
        $product->update(['price' => 12]);
        $response = app(\App\Services\Chatbot\ChatbotService::class)->handle('test-session', 'sim');
        $this->assertSame('order_price_changed', $response['intent']);
        $this->assertSame('awaiting_confirmation', $response['meta']['step']);
        $this->assertSame(0, Sale::count());
        $response = app(\App\Services\Chatbot\ChatbotService::class)->handle('test-session', 'sim');
        $this->assertSame('order_confirmed', $response['intent']);
        $this->assertSame('24.00', Sale::firstOrFail()->total);
    }

    public function test_integrity_migration_round_trip_preserves_existing_data(): void
    {
        $product = $this->product();
        $migration = require database_path('migrations/2026_10_21_000001_add_sale_integrity_fields.php');
        $migration->down();
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('sales', 'checkout_key'));
        $migration->up();
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('sales', 'checkout_key'));
        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(1, StockMovement::count());
    }

    public function test_long_cancellation_reason_and_canceled_checkout_retry(): void
    {
        $product = $this->product();
        $this->cart($product);
        $order = app(ChatbotCheckoutService::class)->finalize('test-session');
        $sale = Sale::findOrFail($order['sale_id']);
        $reason = str_repeat('x', 255);
        app(CancelSaleService::class)->cancel($sale, $reason);
        $this->assertSame($reason, $sale->fresh()->cancellation_reason);
        $this->assertLessThanOrEqual(255, mb_strlen(StockMovement::where('reason', 'return')->firstOrFail()->reference));
        $this->rejects(fn () => app(ChatbotCheckoutService::class)->finalize('test-session'));
        $this->assertSame(1, Sale::count());
        $this->assertSame(10, (int) $product->fresh()->stock);
    }

    public function test_carts_cached_before_the_release_are_upgraded_without_losing_items(): void
    {
        $product = $this->product();
        $cart = $this->cart($product);
        unset($cart['checkout_key']);
        Cache::put('chatbot:cart:test-session', $cart, 60);
        $firstRead = app(CartService::class)->get('test-session');
        $this->assertNotEmpty($firstRead['checkout_key']);
        $this->assertSame($firstRead['checkout_key'], app(CartService::class)->get('test-session')['checkout_key']);
        $order = app(ChatbotCheckoutService::class)->finalize('test-session');
        $this->assertSame(20.0, $order['total']);
        $this->assertSame(8, (int) $product->fresh()->stock);
    }

    public static function productCreateArguments(): array
    {
        return [[[]], [['name' => 'Sugestão da API', 'barcode' => '7891910000197']]];
    }

    #[DataProvider('productCreateArguments')]
    public function test_new_product_form_initializes_active_and_stock_defaults(array $arguments): void
    {
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        \Livewire\Livewire::test(\App\Filament\Resources\Products\Pages\ManageProducts::class)
            ->mountAction('create', arguments: $arguments)
            ->assertActionDataSet(['is_active' => true, 'stock' => 0])
            ->fillForm(['name' => 'Produto com padrões', 'slug' => 'produto-com-padroes', 'price' => 10, 'stock' => 8])
            ->callMountedAction()
            ->assertHasNoActionErrors();
        $product = Product::where('slug', 'produto-com-padroes')->firstOrFail();
        $this->assertTrue($product->is_active);
        $sale = $this->sale($product);
        $this->assertSame('20.00', $sale->total);
    }
}
