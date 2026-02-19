<?php

namespace App\Services\Cart;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class CartService
{
    private const TTL_MINUTES = 120;

    /**
     * @return array{items:array<int,array<string,mixed>>,subtotal:float,total_items:int}
     */
    public function get(string $sessionId): array
    {
        $cart = Cache::get($this->cacheKey($sessionId));

        if (! is_array($cart) || ! isset($cart['items'], $cart['subtotal'], $cart['total_items'])) {
            return $this->emptyCart();
        }

        return $cart;
    }

    /**
     * @param array{unit_price:float,original_price:float|null,is_promotion:bool} $pricing
     * @return array{items:array<int,array<string,mixed>>,subtotal:float,total_items:int}
     */
    public function addItem(string $sessionId, Product $product, int $quantity, array $pricing): array
    {
        $cart = $this->get($sessionId);
        $productId = (int) $product->id;

        if (! isset($cart['items'][$productId])) {
            $cart['items'][$productId] = [
                'product_id' => $productId,
                'name' => (string) $product->name,
                'quantity' => 0,
                'unit_price' => (float) $pricing['unit_price'],
                'original_price' => $pricing['original_price'] !== null ? (float) $pricing['original_price'] : null,
                'is_promotion' => (bool) $pricing['is_promotion'],
            ];
        }

        $cart['items'][$productId]['quantity'] += $quantity;
        $cart['items'][$productId]['unit_price'] = (float) $pricing['unit_price'];
        $cart['items'][$productId]['original_price'] = $pricing['original_price'] !== null ? (float) $pricing['original_price'] : null;
        $cart['items'][$productId]['is_promotion'] = (bool) $pricing['is_promotion'];

        return $this->store($sessionId, $cart);
    }

    /**
     * @return array{items:array<int,array<string,mixed>>,subtotal:float,total_items:int}
     */
    public function removeItem(string $sessionId, int $productId): array
    {
        $cart = $this->get($sessionId);

        unset($cart['items'][$productId]);

        return $this->store($sessionId, $cart);
    }

    public function clear(string $sessionId): void
    {
        Cache::forget($this->cacheKey($sessionId));
    }

    public function itemQuantity(string $sessionId, int $productId): int
    {
        $cart = $this->get($sessionId);

        return (int) ($cart['items'][$productId]['quantity'] ?? 0);
    }

    public function hasItems(string $sessionId): bool
    {
        return $this->get($sessionId)['total_items'] > 0;
    }

    private function cacheKey(string $sessionId): string
    {
        return 'chatbot:cart:' . $sessionId;
    }

    /**
     * @param array{items:array<int,array<string,mixed>>,subtotal:float,total_items:int} $cart
     * @return array{items:array<int,array<string,mixed>>,subtotal:float,total_items:int}
     */
    private function store(string $sessionId, array $cart): array
    {
        $cart = $this->recalculate($cart);

        Cache::put($this->cacheKey($sessionId), $cart, now()->addMinutes(self::TTL_MINUTES));

        return $cart;
    }

    /**
     * @param array{items:array<int,array<string,mixed>>,subtotal:float,total_items:int} $cart
     * @return array{items:array<int,array<string,mixed>>,subtotal:float,total_items:int}
     */
    private function recalculate(array $cart): array
    {
        $subtotal = 0.0;
        $totalItems = 0;

        foreach ($cart['items'] as &$item) {
            $item['quantity'] = (int) $item['quantity'];
            $item['unit_price'] = (float) $item['unit_price'];
            $item['line_total'] = round($item['quantity'] * $item['unit_price'], 2);

            $subtotal += $item['line_total'];
            $totalItems += $item['quantity'];
        }

        unset($item);

        $cart['subtotal'] = round($subtotal, 2);
        $cart['total_items'] = $totalItems;

        return $cart;
    }

    /**
     * @return array{items:array<int,array<string,mixed>>,subtotal:float,total_items:int}
     */
    private function emptyCart(): array
    {
        return [
            'items' => [],
            'subtotal' => 0.0,
            'total_items' => 0,
        ];
    }
}
