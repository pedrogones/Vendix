<?php

namespace App\Services\Sales;

use App\Exceptions\CartPriceChangedException;
use App\Models\Product;
use App\Models\Sale;
use App\Services\Cart\CartService;
use App\Services\Products\ProductPricingService;
use App\Services\Stocks\ConfirmSaleService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ChatbotCheckoutService
{
    public function __construct(private readonly CartService $cartService) {}

    public function finalize(string $sessionId): array
    {
        return $this->cartService->synchronized($sessionId, function () use ($sessionId): array {
            $cart = $this->cartService->get($sessionId);
            $key = $cart['checkout_key'] ?? null;
            if ($key && ($existing = Sale::withTrashed()->where('checkout_key', $key)->first())) {
                $this->cartService->complete($sessionId);

                return $this->result($existing);
            }
            if ($cart['total_items'] === 0 || ! $key) {
                throw new \DomainException('Carrinho vazio. Adicione itens antes de confirmar o pedido.');
            }
            try {
                $sale = DB::transaction(function () use ($cart, $key): Sale {
                    // Insert the unique key before locking products: the database arbitrates duplicate attempts.
                    $sale = Sale::create(['origin' => 'chatbot', 'checkout_key' => $key]);
                    $items = $cart['items'];
                    ksort($items);
                    $pricing = app(ProductPricingService::class);
                    $prices = [];
                    foreach ($items as $item) {
                        $product = Product::query()->lockForUpdate()->find($item['product_id']);
                        if (! $product || ! $product->is_active) {
                            throw new \DomainException('Um produto do carrinho ficou indisponível. Revise os itens.');
                        }
                        $prices[$product->id] = $pricing->price($product);
                        if ($product->stock < (int) $item['quantity']) {
                            throw new \DomainException('Estoque insuficiente para '.$product->name.'. Revise os itens.');
                        }
                    }
                    foreach ($items as $item) {
                        if ($pricing->cents($prices[$item['product_id']]) !== $pricing->cents($item['unit_price'])) {
                            throw new CartPriceChangedException($prices);
                        }
                    }
                    foreach ($items as $item) {
                        $sale->items()->create([
                            'product_id' => $item['product_id'], 'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'], 'discount' => 0,
                            'total_price' => $pricing->lineTotal((int) $item['quantity'], $item['unit_price']),
                        ]);
                    }
                    app(ConfirmSaleService::class)->confirm($sale);

                    return $sale;
                }, 3);
            } catch (CartPriceChangedException $exception) {
                $this->cartService->reprice($sessionId, $exception->prices);
                throw $exception;
            } catch (UniqueConstraintViolationException $exception) {
                $sale = Sale::withTrashed()->where('checkout_key', $key)->first();
                if (! $sale) {
                    throw $exception;
                }
            }
            $this->cartService->complete($sessionId);

            return $this->result($sale);
        });
    }

    private function result(Sale $sale): array
    {
        if ($sale->status !== 'confirmed' || $sale->trashed()) {
            throw new \DomainException('Este pedido não está mais confirmado. Inicie um novo carrinho para fazer outro pedido.');
        }

        return ['sale_id' => (int) $sale->id, 'order_number' => '#'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT), 'total' => (float) $sale->total];
    }
}
