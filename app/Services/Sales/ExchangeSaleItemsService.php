<?php

namespace App\Services\Sales;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleExchange;
use App\Models\SaleExchangeItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Services\Products\ProductPricingService;
use App\Services\Stocks\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ExchangeSaleItemsService
{
    /**
     * @param  array<int, array{sale_item_id:int, quantity:int}>  $returnItems
     * @param  array<int, array{product_id:int, quantity:int}>  $outgoingItems
     * @param  array{method?:string, amount?:int|float|string|null}|null  $settlement
     */
    public function exchange(
        Sale $sale,
        array $returnItems,
        array $outgoingItems,
        string $destination,
        string $reason,
        ?array $settlement = null,
    ): SaleExchange {
        Gate::authorize('return-sales');

        if (trim($reason) === '' || mb_strlen($reason) > 255) {
            throw new \DomainException('Informe o motivo da troca (até 255 caracteres).');
        }
        if ($returnItems === [] || $outgoingItems === []) {
            throw new \DomainException('Informe itens devolvidos e produtos de saída para a troca.');
        }

        return DB::transaction(function () use ($sale, $returnItems, $outgoingItems, $destination, $reason, $settlement): SaleExchange {
            $locked = Sale::query()->lockForUpdate()->with(['items', 'payments', 'returns.items'])->findOrFail($sale->id);
            if ($locked->status !== 'confirmed') {
                throw new \DomainException('Apenas vendas confirmadas aceitam troca.');
            }
            if ($locked->payment_status === Sale::PAYMENT_REFUNDED) {
                throw new \DomainException('Esta venda já foi estornada por completo.');
            }

            $pricing = app(ProductPricingService::class);
            $returns = app(ReturnSaleItemsService::class);
            $creditCents = $returns->creditCentsFor($locked, $returnItems);

            $return = $returns->returnItems(
                $locked,
                $returnItems,
                $destination,
                $reason,
                $locked->payment_status === Sale::PAYMENT_PAID
                    ? ['method' => 'other', 'amount' => 0]
                    : null,
                cancelIfComplete: false,
            );

            $normalizedOut = $this->normalizeOutgoing($outgoingItems);
            $debitCents = 0;
            $exchange = SaleExchange::query()->create([
                'sale_id' => $locked->id,
                'sale_return_id' => $return->id,
                'reason' => $reason,
                'credit_cents' => $creditCents,
                'debit_cents' => 0,
                'settlement_cents' => 0,
                'recorded_by' => auth()->id(),
            ]);

            foreach ($normalizedOut as $row) {
                $product = Product::query()->lockForUpdate()->findOrFail($row['product_id']);
                if (! $product->is_active) {
                    throw new \DomainException(sprintf('O produto "%s" está inativo.', $product->name));
                }
                $unit = $pricing->cents($product->final_price) / 100;
                $lineTotal = $pricing->lineTotal($row['quantity'], $unit, 0);
                $lineCents = $pricing->cents($lineTotal);
                $debitCents += $lineCents;

                SaleExchangeItem::query()->create([
                    'sale_exchange_id' => $exchange->id,
                    'product_id' => $product->id,
                    'quantity' => $row['quantity'],
                    'unit_price' => $unit,
                    'total_price' => $lineTotal,
                ]);

                app(StockService::class)->out(
                    $product,
                    $row['quantity'],
                    'sale',
                    $exchange,
                    mb_substr('Troca: '.$reason, 0, 255),
                );
            }

            $settlementCents = $debitCents - $creditCents;
            $exchange->forceFill([
                'debit_cents' => $debitCents,
                'settlement_cents' => $settlementCents,
            ])->save();

            $this->applySettlement($locked, $settlementCents, $settlement, $reason);

            return $exchange->load(['items', 'saleReturn.items']);
        }, 3);
    }

    /**
     * @param  array<int, array{product_id:int, quantity:int}>  $items
     * @return array<int, array{product_id:int, quantity:int}>
     */
    private function normalizeOutgoing(array $items): array
    {
        $byProduct = [];
        foreach ($items as $index => $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $quantity = (int) ($row['quantity'] ?? 0);
            if ($productId <= 0 || $quantity <= 0) {
                throw new \DomainException('Produto ou quantidade inválidos na saída '.($index + 1).'.');
            }
            $byProduct[$productId] = ($byProduct[$productId] ?? 0) + $quantity;
        }

        $normalized = [];
        foreach ($byProduct as $productId => $quantity) {
            $normalized[] = ['product_id' => $productId, 'quantity' => $quantity];
        }
        usort($normalized, fn (array $a, array $b): int => $a['product_id'] <=> $b['product_id']);

        return $normalized;
    }

    /**
     * @param  array{method?:string, amount?:int|float|string|null}|null  $settlement
     */
    private function applySettlement(Sale $sale, int $settlementCents, ?array $settlement, string $reason): void
    {
        $pricing = app(ProductPricingService::class);

        if ($sale->payment_status === Sale::PAYMENT_UNPAID) {
            if ($settlement !== null && $pricing->cents($settlement['amount'] ?? null) !== 0) {
                throw new \DomainException('Troca de venda não paga não registra acerto financeiro.');
            }

            return;
        }

        if ($settlementCents === 0) {
            if ($settlement !== null && $pricing->cents($settlement['amount'] ?? null) !== 0) {
                throw new \DomainException('Troca sem diferença financeira: informe valor 0 no acerto.');
            }

            return;
        }

        if ($settlement === null) {
            throw new \DomainException('Informe o meio e o valor do acerto financeiro da troca.');
        }

        $method = (string) ($settlement['method'] ?? '');
        if (! in_array($method, SalePayment::METHODS, true)) {
            throw new \DomainException('Meio do acerto inválido.');
        }

        $amountCents = $pricing->cents($settlement['amount'] ?? null);
        if ($amountCents !== abs($settlementCents)) {
            throw new \DomainException(sprintf(
                'Valor do acerto deve ser R$ %s (diferença da troca).',
                number_format(abs($settlementCents) / 100, 2, ',', '.'),
            ));
        }

        $payments = app(RecordSalePaymentService::class);
        if ($settlementCents > 0) {
            $payments->recordAdditional($sale, $method, $amountCents, 'Acerto de troca (+): '.$reason);
        } else {
            $payments->refundAmount($sale, $method, $amountCents, 'Acerto de troca (−): '.$reason);
        }
    }
}
