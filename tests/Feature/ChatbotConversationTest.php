<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\Chatbot\ChatbotService;
use App\Services\Chatbot\ConversationStateService;
use App\Services\Products\ProductWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ChatbotConversationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['create-products', 'create-sales', 'cancel-sales', 'create-stock-movements', 'edit-products'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::all());
        $this->actingAs($user);
        config(['services.gemini.api_key' => '']);
    }

    private function product(string $name, int $stock = 10, float $price = 10): Product
    {
        return app(ProductWriteService::class)->save([
            'name' => $name,
            'slug' => (string) Str::uuid(),
            'price' => $price,
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    public function test_ambiguous_lookup_offers_numbered_choices_and_accepts_selection(): void
    {
        $this->product('Refrigerante Cola 2L');
        $this->product('Refrigerante Laranja 2L');

        $first = app(ChatbotService::class)->handle('chat-ambig', 'qual o preco do refrigerante');
        $this->assertSame('product_choices', $first['intent']);
        $this->assertStringContainsString('1)', $first['message']);
        $this->assertStringContainsString('2)', $first['message']);

        $second = app(ChatbotService::class)->handle('chat-ambig', '1');
        $this->assertSame('product_lookup', $second['intent']);
        $this->assertStringContainsString('Refrigerante Cola 2L', $second['message']);
    }

    public function test_order_start_uses_quantity_from_message_without_extra_step(): void
    {
        $product = $this->product('Agua Mineral 500ml');

        $response = app(ChatbotService::class)->handle('chat-qty', 'quero 3 agua mineral');
        $this->assertSame('order_item_added', $response['intent']);
        $this->assertSame(3, $response['meta']['quantity_added']);
        $this->assertSame(3, $response['meta']['cart_total_items']);
        $this->assertSame('idle', app(ConversationStateService::class)->get('chat-qty')['step']);
        $this->assertSame($product->id, $response['meta']['product_id']);
    }

    public function test_cart_summary_lists_all_items_not_only_five(): void
    {
        $names = ['AlphaOne', 'BravoTwo', 'CharlieThree', 'DeltaFour', 'EchoFive', 'FoxtrotSix'];
        foreach ($names as $name) {
            $this->product($name, 20, 5);
        }

        $chat = app(ChatbotService::class);
        foreach ($names as $name) {
            $added = $chat->handle('chat-sum', 'quero 1 '.$name);
            $this->assertSame('order_item_added', $added['intent'], $added['message']);
        }

        $summary = $chat->handle('chat-sum', 'resumo do carrinho');
        $this->assertSame('order_summary', $summary['intent']);
        foreach ($names as $name) {
            $this->assertStringContainsString($name, $summary['message']);
        }
        $this->assertSame(6, $summary['meta']['cart_total_items']);
    }

    public function test_voltar_cancels_awaiting_quantity_and_keeps_idle(): void
    {
        $this->product('Suco Uva 1L');
        $chat = app(ChatbotService::class);
        $start = $chat->handle('chat-back', 'quero suco uva');
        $this->assertSame('order_start', $start['intent']);
        $this->assertSame('awaiting_quantity', app(ConversationStateService::class)->get('chat-back')['step']);

        $cancel = $chat->handle('chat-back', 'voltar');
        $this->assertSame('step_canceled', $cancel['intent']);
        $this->assertSame('idle', app(ConversationStateService::class)->get('chat-back')['step']);
        $this->assertSame(0, $cancel['meta']['cart_total_items']);
    }

    public function test_chatbot_endpoint_is_rate_limited(): void
    {
        $bucket = 'chatbot-rl-'.uniqid('', true);
        RateLimiter::for('chatbot', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(1)->by($bucket));

        $this->postJson(route('chatbot.message'), ['message' => 'ola'])->assertOk();
        $this->postJson(route('chatbot.message'), ['message' => 'ola'])->assertStatus(429);
    }
}
