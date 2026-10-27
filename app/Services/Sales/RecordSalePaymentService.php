<?php

namespace App\Services\Sales;

use App\Models\Sale;
use App\Models\SalePayment;
use App\Services\Products\ProductPricingService;
use Illuminate\Support\Facades\DB;

class RecordSalePaymentService
{
    public function record(Sale $sale, array $payments): void
    {
        DB::transaction(function () use ($sale, $payments): void {
            $locked = Sale::query()->lockForUpdate()->with('payments')->findOrFail($sale->id);
            if ($locked->status !== 'confirmed') {
                throw new \DomainException('Só é possível registrar recebimento em venda confirmada.');
            }
            if ($locked->payment_status === Sale::PAYMENT_PAID) {
                throw new \DomainException('Esta venda já está paga.');
            }
            if ($locked->payment_status === Sale::PAYMENT_REFUNDED) {
                throw new \DomainException('Esta venda já foi estornada.');
            }
            if ($payments === []) {
                throw new \DomainException('Informe ao menos um recebimento.');
            }

            $pricing = app(ProductPricingService::class);
            $totalCents = $pricing->cents($locked->total);
            $sumCents = 0;

            foreach ($payments as $index => $payment) {
                $method = (string) ($payment['method'] ?? '');
                if (! in_array($method, SalePayment::METHODS, true)) {
                    throw new \DomainException('Meio de pagamento inválido na linha '.($index + 1).'.');
                }
                $amountCents = $pricing->cents($payment['amount'] ?? null);
                if ($amountCents <= 0) {
                    throw new \DomainException('Valor de recebimento inválido na linha '.($index + 1).'.');
                }

                $tenderedCents = null;
                $changeCents = 0;
                if ($method === 'cash') {
                    $tenderedRaw = $payment['amount_tendered'] ?? $payment['amount'];
                    $tenderedCents = $pricing->cents($tenderedRaw);
                    if ($tenderedCents < $amountCents) {
                        throw new \DomainException('Valor recebido em dinheiro menor que o valor pago na linha '.($index + 1).'.');
                    }
                    $changeCents = $tenderedCents - $amountCents;
                } elseif (array_key_exists('amount_tendered', $payment) && $payment['amount_tendered'] !== null && $payment['amount_tendered'] !== '') {
                    throw new \DomainException('Valor recebido/troco só se aplica a dinheiro.');
                }

                $sumCents += $amountCents;
                SalePayment::query()->create([
                    'sale_id' => $locked->id,
                    'type' => 'payment',
                    'method' => $method,
                    'amount' => $amountCents / 100,
                    'amount_tendered' => $tenderedCents !== null ? $tenderedCents / 100 : null,
                    'change_amount' => $changeCents / 100,
                    'recorded_by' => auth()->id(),
                    'notes' => isset($payment['notes']) ? mb_substr((string) $payment['notes'], 0, 255) : null,
                ]);
            }

            if ($sumCents !== $totalCents) {
                throw new \DomainException('A soma dos recebimentos deve ser igual ao total da venda.');
            }

            $locked->markPaymentStatus(Sale::PAYMENT_PAID);
        }, 3);
        $sale->refresh();
    }

    public function refundAll(Sale $sale): void
    {
        DB::transaction(function () use ($sale): void {
            $locked = Sale::query()->lockForUpdate()->with('payments')->findOrFail($sale->id);
            if ($locked->payment_status === Sale::PAYMENT_REFUNDED) {
                throw new \DomainException('Esta venda já foi estornada.');
            }
            if ($locked->payment_status !== Sale::PAYMENT_PAID) {
                return;
            }

            $pricing = app(ProductPricingService::class);
            foreach ($locked->payments->where('type', 'payment') as $payment) {
                SalePayment::query()->create([
                    'sale_id' => $locked->id,
                    'type' => 'refund',
                    'method' => $payment->method,
                    'amount' => $pricing->cents($payment->amount) / 100,
                    'amount_tendered' => null,
                    'change_amount' => 0,
                    'recorded_by' => auth()->id(),
                    'notes' => 'Estorno do recebimento #'.$payment->id,
                ]);
            }

            $locked->markPaymentStatus(Sale::PAYMENT_REFUNDED);
        }, 3);
        $sale->refresh();
    }
}
