<?php

namespace App\Services\Sales;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Cart\CartService;
use Illuminate\Support\Facades\DB;

class ChatbotCheckoutService
{
    public function __construct(private readonly CartService $cartService)
    {
    }

    /**
     * @return array{sale_id:int,order_number:string,total:float}
     */
    public function finalize(string $sessionId): array
    {
        $cart = $this->cartService->get($sessionId);

        if ($cart['total_items'] === 0) {
            throw new \DomainException('Carrinho vazio. Adicione itens antes de confirmar o pedido.');
        }

        $sale = null;

        DB::transaction(function () use (&$sale, $cart): void {
            $sale = Sale::create([
                'client_id' => null,
                'user_id' => null,
                'origin' => 'chatbot',
            ]);

            $total = 0.0;

            foreach ($cart['items'] as $item) {
                $product = Product::query()
                    ->whereKey((int) $item['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    throw new \DomainException(sprintf('Produto ID %d nao encontrado para concluir pedido.', (int) $item['product_id']));
                }

                if (! $product->is_active) {
                    throw new \DomainException(sprintf('O produto "%s" ficou inativo durante a confirmacao.', $product->name));
                }

                $quantity = (int) $item['quantity'];

                if ($quantity <= 0) {
                    throw new \DomainException(sprintf('Quantidade invalida para "%s".', $product->name));
                }

                if ($product->stock < $quantity) {
                    throw new \DomainException(sprintf(
                        'Estoque insuficiente para "%s". Disponivel: %d unidade(s).',
                        $product->name,
                        (int) $product->stock
                    ));
                }

                $unitPrice = (float) $item['unit_price'];
                $lineTotal = round($unitPrice * $quantity, 2);
                $discount = null;

                if (isset($item['original_price']) && $item['original_price'] !== null) {
                    $original = (float) $item['original_price'];

                    if ($original > $unitPrice) {
                        $discount = round(($original - $unitPrice) * $quantity, 2);
                    }
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                    'discount' => $discount,
                ]);

                $product->decrement('stock', $quantity);
                $total += $lineTotal;
            }

            $sale->update([
                'status' => 'confirmed',
                'total' => round($total, 2),
            ]);
        });

        if (! $sale instanceof Sale) {
            throw new \RuntimeException('Falha ao finalizar pedido do chatbot.');
        }

        $this->cartService->clear($sessionId);

        return [
            'sale_id' => (int) $sale->id,
            'order_number' => $this->formatOrderNumber((int) $sale->id),
            'total' => (float) $sale->total,
        ];
    }

    private function formatOrderNumber(int $saleId): string
    {
        return '#' . str_pad((string) $saleId, 6, '0', STR_PAD_LEFT);
    }
}
