<?php

namespace App\Services\Sales;

use App\Exceptions\CartPriceChangedException;
use App\Models\Product;
use App\Models\Sale;
use App\Services\Cart\CartService;
use App\Services\Chatbot\ConversationStateService;
use App\Services\Products\ProductPricingService;
use App\Services\Stocks\ConfirmSaleService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ChatbotCheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ConversationStateService $stateService,
    ) {}

    /**
     * @param  array{
     *     buyer_name:string,
     *     buyer_phone:string,
     *     fulfillment_type:string,
     *     delivery_address:?string,
     *     needs_human_help?:bool,
     *     human_help_note?:?string
     * }  $checkout
     */
    public function finalize(string $sessionId, array $checkout = []): array
    {
        return $this->cartService->synchronized($sessionId, function () use ($sessionId, $checkout): array {
            $cart = $this->cartService->get($sessionId);
            $key = $cart['checkout_key'] ?? null;
            if ($key && ($existing = Sale::withTrashed()->where('checkout_key', $key)->first())) {
                $this->cartService->complete($sessionId);
                $this->stateService->reset($sessionId);

                return $this->result($existing);
            }
            if ($cart['total_items'] === 0 || ! $key) {
                throw new \DomainException('Carrinho vazio. Adicione itens antes de confirmar o pedido.');
            }

            $checkout = $this->normalizeCheckout($checkout ?: $this->checkoutFromState($sessionId));

            try {
                $sale = DB::transaction(function () use ($cart, $key, $checkout): Sale {
                    $sale = Sale::create([
                        'origin' => 'chatbot',
                        'checkout_key' => $key,
                        'buyer_name' => $checkout['buyer_name'],
                        'buyer_phone' => $checkout['buyer_phone'],
                        'fulfillment_type' => $checkout['fulfillment_type'],
                        'delivery_address' => $checkout['delivery_address'],
                        'fulfillment_status' => Sale::FULFILLMENT_RECEIVED,
                        'needs_human_help' => $checkout['needs_human_help'],
                        'human_help_note' => $checkout['human_help_note'],
                        'public_token' => Sale::newPublicToken(),
                    ]);
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

                    return $sale->fresh();
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
            $this->stateService->reset($sessionId);

            return $this->result($sale);
        });
    }

    /**
     * @return array{
     *     buyer_name:string,
     *     buyer_phone:string,
     *     fulfillment_type:string,
     *     delivery_address:?string,
     *     needs_human_help:bool,
     *     human_help_note:?string
     * }
     */
    private function checkoutFromState(string $sessionId): array
    {
        $state = $this->stateService->get($sessionId);

        return [
            'buyer_name' => (string) ($state['buyer_name'] ?? ''),
            'buyer_phone' => (string) ($state['buyer_phone'] ?? ''),
            'fulfillment_type' => (string) ($state['fulfillment_type'] ?? ''),
            'delivery_address' => $state['delivery_address'] ?? null,
            'needs_human_help' => (bool) ($state['needs_human_help'] ?? false),
            'human_help_note' => $state['human_help_note'] ?? null,
        ];
    }

    /**
     * @param  array<string,mixed>  $checkout
     * @return array{
     *     buyer_name:string,
     *     buyer_phone:string,
     *     fulfillment_type:string,
     *     delivery_address:?string,
     *     needs_human_help:bool,
     *     human_help_note:?string
     * }
     */
    private function normalizeCheckout(array $checkout): array
    {
        $name = trim((string) ($checkout['buyer_name'] ?? ''));
        $phone = Sale::normalizePhone((string) ($checkout['buyer_phone'] ?? ''));
        $type = (string) ($checkout['fulfillment_type'] ?? '');
        $address = trim((string) ($checkout['delivery_address'] ?? ''));

        if ($name === '' || mb_strlen($name) > 120) {
            throw new \DomainException('Informe o nome para contato (até 120 caracteres).');
        }
        if (strlen($phone) < 10 || strlen($phone) > 13) {
            throw new \DomainException('Informe um telefone válido com DDD.');
        }
        if (! in_array($type, [Sale::FULFILLMENT_PICKUP, Sale::FULFILLMENT_DELIVERY], true)) {
            throw new \DomainException('Escolha retirada ou entrega.');
        }
        if ($type === Sale::FULFILLMENT_DELIVERY && ($address === '' || mb_strlen($address) > 255)) {
            throw new \DomainException('Informe o endereço de entrega (até 255 caracteres).');
        }

        $note = isset($checkout['human_help_note']) ? trim((string) $checkout['human_help_note']) : null;
        if ($note === '') {
            $note = null;
        }
        if ($note !== null && mb_strlen($note) > 255) {
            $note = mb_substr($note, 0, 255);
        }

        return [
            'buyer_name' => $name,
            'buyer_phone' => $phone,
            'fulfillment_type' => $type,
            'delivery_address' => $type === Sale::FULFILLMENT_DELIVERY ? $address : null,
            'needs_human_help' => (bool) ($checkout['needs_human_help'] ?? false),
            'human_help_note' => $note,
        ];
    }

    private function result(Sale $sale): array
    {
        if ($sale->status !== 'confirmed' || $sale->trashed()) {
            throw new \DomainException('Este pedido não está mais confirmado. Inicie um novo carrinho para fazer outro pedido.');
        }

        return [
            'sale_id' => (int) $sale->id,
            'order_number' => $sale->orderNumber(),
            'total' => (float) $sale->total,
            'buyer_phone' => (string) $sale->buyer_phone,
            'public_token' => (string) $sale->public_token,
            'fulfillment_type' => (string) $sale->fulfillment_type,
            'fulfillment_status' => (string) $sale->fulfillment_status,
        ];
    }
}
