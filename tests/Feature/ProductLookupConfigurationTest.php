<?php

namespace Tests\Feature;

use App\Services\Products\ProductLookupService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductLookupConfigurationTest extends TestCase
{
    public function test_lookup_uses_configured_endpoint_token_and_account_user_agent(): void
    {
        config(['services.bluesoft' => [
            'url' => 'https://cosmos.example/api/',
            'token' => 'test-token',
            'user_agent' => 'test-account-agent',
        ]]);
        Http::preventStrayRequests();
        Http::fake(['https://cosmos.example/api/gtins/7891910000197.json' => Http::response([
            'description' => 'Produto de teste',
            'brand' => ['name' => 'Marca teste'],
        ])]);

        $result = app(ProductLookupService::class)->lookup('7891910000197');

        $this->assertSame('Produto de teste', $result['name']);
        $this->assertSame('Marca teste', $result['brand']);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://cosmos.example/api/gtins/7891910000197.json'
            && $request->hasHeader('X-Cosmos-Token', 'test-token')
            && $request->hasHeader('User-Agent', 'test-account-agent'));
    }

    public function test_product_not_found_still_returns_null(): void
    {
        config(['services.bluesoft.url' => 'https://cosmos.example/api']);
        Http::preventStrayRequests();
        Http::fake(['https://cosmos.example/api/gtins/7891910000197.json' => Http::response([], 404)]);

        $this->assertNull(app(ProductLookupService::class)->lookup('7891910000197'));
    }
}
