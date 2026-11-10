<?php

namespace App\Services\Sales;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Services\Products\ProductPricingService;
use App\Services\Stocks\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReturnSaleItemsService
{
    /**
     * @param  array<int, array{sale_item_id:int, quantity:int}>  $items
     * @param  array{method?:string, amount?:int|float|string|null}|null  $refund
     */
    public function returnItems(
        Sale $sale,
        array $items,
        string $destination,
        string $reason,
        ?array $refund = null,
        bool $cancelIfComplete = true,
    ): SaleReturn {
        Gate::authorize('return-sales');

        if (trim($reason) === '' || mb_strlen($reason) > 255) {
            throw new \DomainException('Informe o motivo da devolução (até 255 caracteres).');
        }
        if (! in_array($destination, SaleReturn::DESTINATIONS, true)) {
            throw new \DomainException('Destino da devolução inválido.');
        }
        if ($items === []) {
            throw new \DomainException('Informe ao menos um item para devolver.');
        }

        return DB::transaction(function () use ($sale, $items, $destination, $reason, $refund, $cancelIfComplete): SaleReturn {
            $locked = Sale::query()->lockForUpdate()->with(['items', 'payments', 'returns.items'])->findOrFail($sale->id);
            if ($locked->status !== 'confirmed') {
                throw new \DomainException('Apenas vendas confirmadas aceitam devolução parcial.');
            }
            if ($locked->payment_status === Sale::PAYMENT_REFUNDED) {
                throw new \DomainException('Esta venda já foi estornada por completo.');
            }

            $return = SaleReturn::query()->create([
                'sale_id' => $locked->id,
                'destination' => $destination,
                'reason' => $reason,
                'recorded_by' => auth()->id(),
            ]);

            $normalized = $this->normalizeItems($locked, $items);
            foreach ($normalized as $row) {
                /** @var SaleItem $saleItem */
                $saleItem = $row['sale_item'];
                $quantity = $row['quantity'];

                SaleReturnItem::query()->create([
                    'sale_return_id' => $return->id,
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'quantity' => $quantity,
                ]);

                if ($destination === SaleReturn::DESTINATION_RESTOCK) {
                    $product = Product::query()->lockForUpdate()->find($saleItem->product_id);
                    if (! $product) {
                        throw new \DomainException('Produto da venda não encontrado para devolução.');
                    }
                    app(StockService::class)->in(
                        $product,
                        $quantity,
                        'return',
                        $return,
                        mb_substr('Devolução parcial: '.$reason, 0, 255),
                    );
                }
            }

            $this->applyRefund($locked, $refund, $reason);

            $locked->unsetRelation('returns');
            $locked->load(['items', 'returns.items', 'payments']);

            if (
                $cancelIfComplete
                && $locked->allItemsFullyReturned()
                && in_array($locked->payment_status, [Sale::PAYMENT_UNPAID, Sale::PAYMENT_REFUNDED], true)
            ) {
                $locked->transitionTo('canceled', [
                    'cancellation_reason' => mb_substr('Devolução total: '.$reason, 0, 255),
                    'canceled_by' => auth()->id(),
                    'canceled_at' => now(),
                ]);
            }

            return $return->load('items');
        }, 3);
    }

    /**
     * Crédito proporcional ao total do item original (em centavos).
     *
     * @param  array<int, array{sale_item_id:int, quantity:int}>  $items
     */
    public function creditCentsFor(Sale $sale, array $items): int
    {
        $sale->loadMissing('items');
        $pricing = app(ProductPricingService::class);
        $credit = 0;
        foreach ($items as $row) {
            $saleItem = $sale->items->firstWhere('id', (int) ($row['sale_item_id'] ?? 0));
            if (! $saleItem) {
                throw new \DomainException('Item inválido para cálculo de crédito.');
            }
            $quantity = (int) ($row['quantity'] ?? 0);
            if ($quantity <= 0) {
                continue;
            }
            $lineCents = $pricing->cents($saleItem->total_price);
            $credit += (int) round($lineCents * $quantity / max(1, (int) $saleItem->quantity));
        }

        return $credit;
    }

    /**
     * @param  array<int, array{sale_item_id:int, quantity:int}>  $items
     * @return array<int, array{sale_item:SaleItem, quantity:int}>
     */
    private function normalizeItems(Sale $sale, array $items): array
    {
        $byItem = [];
        foreach ($items as $index => $row) {
            $saleItemId = (int) ($row['sale_item_id'] ?? 0);
            $quantity = (int) ($row['quantity'] ?? 0);
            if ($saleItemId <= 0 || $quantity <= 0) {
                throw new \DomainException('Item ou quantidade inválidos na linha '.($index + 1).'.');
            }
            $byItem[$saleItemId] = ($byItem[$saleItemId] ?? 0) + $quantity;
        }

        $normalized = [];
        foreach ($byItem as $saleItemId => $quantity) {
            $saleItem = $sale->items->firstWhere('id', $saleItemId);
            if (! $saleItem) {
                throw new \DomainException('Item '.$saleItemId.' não pertence a esta venda.');
            }
            $remaining = $sale->remainingQuantityForItem($saleItem);
            if ($quantity > $remaining) {
                throw new \DomainException(sprintf(
                    'Quantidade devolvida acima do residual do item "%s". Disponível: %d.',
                    $saleItem->product?->name ?? '#'.$saleItem->id,
                    $remaining,
                ));
            }
            $normalized[] = ['sale_item' => $saleItem, 'quantity' => $quantity];
        }

        usort($normalized, fn (array $a, array $b): int => $a['sale_item']->product_id <=> $b['sale_item']->product_id
            ?: $a['sale_item']->id <=> $b['sale_item']->id);

        return $normalized;
    }

    /**
     * @param  array{method?:string, amount?:int|float|string|null}|null  $refund
     */
    private function applyRefund(Sale $sale, ?array $refund, string $reason): void
    {
        $pricing = app(ProductPricingService::class);

        if ($sale->payment_status === Sale::PAYMENT_UNPAID) {
            if ($refund !== null && $pricing->cents($refund['amount'] ?? null) > 0) {
                throw new \DomainException('Venda não paga não aceita estorno financeiro.');
            }

            return;
        }

        if ($refund === null) {
            throw new \DomainException('Informe o efeito financeiro do estorno (valor e meio), ou zero para não estornar agora.');
        }

        $amountCents = $pricing->cents($refund['amount'] ?? null);
        if ($amountCents < 0) {
            throw new \DomainException('Valor de estorno inválido.');
        }
        if ($amountCents === 0) {
            return;
        }

        $method = (string) ($refund['method'] ?? '');
        if (! in_array($method, SalePayment::METHODS, true)) {
            throw new \DomainException('Meio de estorno inválido.');
        }

        app(RecordSalePaymentService::class)->refundAmount(
            $sale,
            $method,
            $amountCents,
            'Devolução parcial: '.$reason,
        );
    }
}
