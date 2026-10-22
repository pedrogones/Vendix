<?php

namespace App\Services\Cart;

use App\Models\Product;
use App\Services\Products\ProductPricingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CartService
{
    private const TTL_MINUTES = 120;

    private array $locks = [];

    public function synchronized(string $sessionId, callable $callback): mixed
    {
        if (isset($this->locks[$sessionId])) {
            return $callback();
        }

        return Cache::lock('chatbot:lock:'.hash('sha256', $sessionId), 120)->block(5, function () use ($sessionId, $callback): mixed {
            $this->locks[$sessionId] = true;
            try {
                return $callback();
            } finally {
                unset($this->locks[$sessionId]);
            }
        });
    }

    public function get(string $sessionId): array
    {
        $cart = Cache::get($this->cacheKey($sessionId));
        if (! is_array($cart) || ! isset($cart['items'], $cart['subtotal'], $cart['total_items'])) {
            return $this->emptyCart();
        }

        if ($cart['total_items'] > 0 && empty($cart['checkout_key'])) {
            // Upgrade carts cached before this release under the same session lock.
            return $this->synchronized($sessionId, function () use ($sessionId): array {
                $latest = Cache::get($this->cacheKey($sessionId));
                if (! is_array($latest) || ! isset($latest['items'], $latest['subtotal'], $latest['total_items'])) {
                    return $this->emptyCart();
                }

                return ! empty($latest['checkout_key']) ? $latest : $this->store($sessionId, $latest);
            });
        }

        return $cart;
    }

    public function addItem(string $sessionId, Product $product, int $quantity, array $pricing): array
    {
        return $this->synchronized($sessionId, function () use ($sessionId, $product, $quantity): array {
            $cart = $this->get($sessionId);
            $product = Product::findOrFail($product->id);
            $id = (int) $product->id;
            $newQuantity = (int) ($cart['items'][$id]['quantity'] ?? 0) + $quantity;
            if ($quantity <= 0 || ! $product->is_active || $newQuantity > $product->stock) {
                throw new \DomainException('Produto indisponível ou quantidade acima do estoque.');
            }
            $price = app(ProductPricingService::class)->price($product);
            $promotion = app(ProductPricingService::class)->isPromotion($product);
            $cart['items'][$id] = [
                'product_id' => $id, 'name' => $product->name, 'quantity' => $newQuantity,
                'unit_price' => $price, 'original_price' => $promotion ? (float) $product->price : null,
                'is_promotion' => $promotion,
            ];

            return $this->store($sessionId, $cart, true);
        });
    }

    public function removeItem(string $sessionId, int $productId): array
    {
        return $this->synchronized($sessionId, function () use ($sessionId, $productId): array {
            $cart = $this->get($sessionId);
            unset($cart['items'][$productId]);

            return $this->store($sessionId, $cart, true);
        });
    }

    public function reprice(string $sessionId, array $prices): array
    {
        return $this->synchronized($sessionId, function () use ($sessionId, $prices): array {
            $cart = $this->get($sessionId);
            foreach ($prices as $id => $price) {
                if (isset($cart['items'][$id])) {
                    $cart['items'][$id]['unit_price'] = $price;
                    $cart['items'][$id]['original_price'] = null;
                    $cart['items'][$id]['is_promotion'] = false;
                }
            }

            return $this->store($sessionId, $cart, true);
        });
    }

    public function complete(string $sessionId): void
    {
        $this->synchronized($sessionId, function () use ($sessionId): void {
            $cart = $this->get($sessionId);
            $cart['items'] = [];
            // Keep the completed checkout key so retries recover the same persisted sale.
            $this->store($sessionId, $cart);
        });
    }

    public function clear(string $sessionId): void
    {
        $this->synchronized($sessionId, fn () => Cache::forget($this->cacheKey($sessionId)));
    }

    public function itemQuantity(string $sessionId, int $productId): int
    {
        return (int) ($this->get($sessionId)['items'][$productId]['quantity'] ?? 0);
    }

    public function hasItems(string $sessionId): bool
    {
        return $this->get($sessionId)['total_items'] > 0;
    }

    private function cacheKey(string $sessionId): string
    {
        return 'chatbot:cart:'.$sessionId;
    }

    private function store(string $sessionId, array $cart, bool $newRevision = false): array
    {
        $cart['checkout_key'] = $newRevision ? (string) Str::uuid() : ($cart['checkout_key'] ?? (string) Str::uuid());
        $totalCents = 0;
        $cart['total_items'] = 0;
        foreach ($cart['items'] as &$item) {
            $item['line_total'] = app(ProductPricingService::class)->lineTotal((int) $item['quantity'], $item['unit_price']);
            $totalCents += app(ProductPricingService::class)->cents($item['line_total']);
            $cart['total_items'] += (int) $item['quantity'];
        }
        unset($item);
        $cart['subtotal'] = $totalCents / 100.0;
        Cache::put($this->cacheKey($sessionId), $cart, now()->addMinutes(self::TTL_MINUTES));

        return $cart;
    }

    private function emptyCart(): array
    {
        return ['items' => [], 'subtotal' => 0.0, 'total_items' => 0, 'checkout_key' => null];
    }
}
