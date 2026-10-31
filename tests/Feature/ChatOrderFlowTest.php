<?php

namespace Tests\Feature;

use App\Filament\Pages\ChatOrders;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Chatbot\ChatbotService;
use App\Services\Chatbot\ConversationStateService;
use App\Services\Products\ProductWriteService;
use App\Services\Sales\ChatbotCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ChatOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            'create-products', 'create-stock-movements', 'edit-products',
            'view-chat-orders', 'manage-chat-orders',
        ] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::all());
        $this->actingAs($user);
        config(['services.gemini.api_key' => '']);
    }

    private function product(string $name = 'Agua Mineral', int $stock = 10): Product
    {
        return app(ProductWriteService::class)->save([
            'name' => $name,
            'slug' => (string) Str::uuid(),
            'price' => 10,
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    public function test_chat_checkout_collects_contact_pickup_and_persists_fulfillment(): void
    {
        $product = $this->product();
        app(CartService::class)->addItem('flow-1', $product, 2, []);
        $chat = app(ChatbotService::class);

        $start = $chat->handle('flow-1', 'finalizar pedido');
        $this->assertSame('awaiting_contact', $start['intent']);

        $type = $chat->handle('flow-1', 'Maria Silva 11988887777');
        $this->assertSame('awaiting_fulfillment_type', $type['intent']);

        $confirm = $chat->handle('flow-1', 'retirada');
        $this->assertSame('order_confirm', $confirm['intent']);

        $done = $chat->handle('flow-1', 'sim');
        $this->assertSame('order_confirmed', $done['intent']);
        $sale = Sale::firstOrFail();
        $this->assertSame('Maria Silva', $sale->buyer_name);
        $this->assertSame('11988887777', $sale->buyer_phone);
        $this->assertSame(Sale::FULFILLMENT_PICKUP, $sale->fulfillment_type);
        $this->assertSame(Sale::FULFILLMENT_RECEIVED, $sale->fulfillment_status);
        $this->assertNotEmpty($sale->public_token);
    }

    public function test_delivery_requires_address_and_public_lookup_works(): void
    {
        $product = $this->product('Suco Uva');
        app(CartService::class)->addItem('flow-2', $product, 1, []);
        $order = app(ChatbotCheckoutService::class)->finalize('flow-2', [
            'buyer_name' => 'Joao',
            'buyer_phone' => '11977776666',
            'fulfillment_type' => 'delivery',
            'delivery_address' => 'Rua das Flores 100, Centro',
        ]);

        $this->assertDatabaseHas('sales', [
            'id' => $order['sale_id'],
            'fulfillment_type' => 'delivery',
            'delivery_address' => 'Rua das Flores 100, Centro',
        ]);

        $this->post(route('order-status.lookup'), [
            'order_number' => $order['order_number'],
            'phone' => '11977776666',
        ])->assertOk()->assertSee('Recebido pela loja', false)->assertSee('Entrega', false);

        $this->post(route('order-status.lookup'), [
            'order_number' => $order['order_number'],
            'phone' => '11900001111',
        ])->assertOk()->assertSee('Não encontramos', false);
    }

    public function test_human_help_flags_session_and_existing_sale(): void
    {
        $product = $this->product('Cha Verde');
        app(CartService::class)->addItem('flow-3', $product, 1, []);
        $order = app(ChatbotCheckoutService::class)->finalize('flow-3', [
            'buyer_name' => 'Ana',
            'buyer_phone' => '11966665555',
            'fulfillment_type' => 'pickup',
            'needs_human_help' => false,
        ]);

        app(ConversationStateService::class)->update('flow-3', [
            'buyer_phone' => '11966665555',
            'buyer_name' => 'Ana',
        ]);
        $help = app(ChatbotService::class)->handle('flow-3', 'quero falar com um atendente');
        $this->assertSame('human_help', $help['intent']);
        $this->assertTrue((bool) app(ConversationStateService::class)->get('flow-3')['needs_human_help']);
        $this->assertTrue((bool) Sale::findOrFail($order['sale_id'])->fresh()->needs_human_help);
    }

    public function test_store_can_advance_fulfillment_status(): void
    {
        $product = $this->product('Cafe');
        app(CartService::class)->addItem('flow-4', $product, 1, []);
        $order = app(ChatbotCheckoutService::class)->finalize('flow-4', [
            'buyer_name' => 'Pedro',
            'buyer_phone' => '11955554444',
            'fulfillment_type' => 'pickup',
        ]);

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        Livewire::test(ChatOrders::class)
            ->call('advanceStatus', $order['sale_id'])
            ->assertHasNoErrors();

        $this->assertSame(Sale::FULFILLMENT_PREPARING, Sale::findOrFail($order['sale_id'])->fulfillment_status);
    }
}
