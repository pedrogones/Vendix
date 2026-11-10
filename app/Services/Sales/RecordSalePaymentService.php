<?php

namespace App\Services\Sales;

use App\Models\Sale;
use App\Models\SalePayment;
use App\Services\Cash\CashSessionService;
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

            $sessionId = app(CashSessionService::class)->currentOpen()?->id;
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
                    'cash_session_id' => $sessionId,
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

    public function recordAdditional(Sale $sale, string $method, int $amountCents, ?string $notes = null): void
    {
        if ($amountCents <= 0) {
            throw new \DomainException('Valor de recebimento adicional inválido.');
        }
        if (! in_array($method, SalePayment::METHODS, true)) {
            throw new \DomainException('Meio de pagamento inválido.');
        }

        DB::transaction(function () use ($sale, $method, $amountCents, $notes): void {
            $locked = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            if ($locked->status !== 'confirmed') {
                throw new \DomainException('Só é possível registrar recebimento adicional em venda confirmada.');
            }
            if ($locked->payment_status !== Sale::PAYMENT_PAID) {
                throw new \DomainException('Recebimento adicional só se aplica a venda paga.');
            }

            $sessionId = app(CashSessionService::class)->currentOpen()?->id;
            SalePayment::query()->create([
                'sale_id' => $locked->id,
                'cash_session_id' => $sessionId,
                'type' => 'payment',
                'method' => $method,
                'amount' => $amountCents / 100,
                'amount_tendered' => null,
                'change_amount' => 0,
                'recorded_by' => auth()->id(),
                'notes' => $notes !== null ? mb_substr($notes, 0, 255) : null,
            ]);
        }, 3);
        $sale->refresh();
    }

    public function refundAmount(Sale $sale, string $method, int $amountCents, ?string $notes = null): void
    {
        if ($amountCents < 0) {
            throw new \DomainException('Valor de estorno inválido.');
        }
        if ($amountCents === 0) {
            return;
        }
        if (! in_array($method, SalePayment::METHODS, true)) {
            throw new \DomainException('Meio de estorno inválido.');
        }

        DB::transaction(function () use ($sale, $method, $amountCents, $notes): void {
            $locked = Sale::query()->lockForUpdate()->with('payments')->findOrFail($sale->id);
            if ($locked->payment_status === Sale::PAYMENT_REFUNDED) {
                throw new \DomainException('Esta venda já foi estornada.');
            }
            if ($locked->payment_status !== Sale::PAYMENT_PAID) {
                throw new \DomainException('Só é possível estornar venda paga.');
            }

            $net = $locked->amountPaidCents();
            if ($amountCents > $net) {
                throw new \DomainException('Estorno maior que o valor líquido recebido.');
            }

            $sessionId = app(CashSessionService::class)->currentOpen()?->id;
            SalePayment::query()->create([
                'sale_id' => $locked->id,
                'cash_session_id' => $sessionId,
                'type' => 'refund',
                'method' => $method,
                'amount' => $amountCents / 100,
                'amount_tendered' => null,
                'change_amount' => 0,
                'recorded_by' => auth()->id(),
                'notes' => $notes !== null ? mb_substr($notes, 0, 255) : null,
            ]);

            $locked->unsetRelation('payments');
            $locked->load('payments');
            if ($locked->amountPaidCents() === 0) {
                $locked->markPaymentStatus(Sale::PAYMENT_REFUNDED);
            }
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
            $sessionId = app(CashSessionService::class)->currentOpen()?->id;
            $refundPoolByMethod = [];
            foreach ($locked->payments->where('type', 'refund') as $refund) {
                $method = (string) $refund->method;
                $refundPoolByMethod[$method] = ($refundPoolByMethod[$method] ?? 0) + $pricing->cents($refund->amount);
            }

            foreach ($locked->payments->where('type', 'payment') as $payment) {
                $method = (string) $payment->method;
                $paymentCents = $pricing->cents($payment->amount);
                $already = min($paymentCents, $refundPoolByMethod[$method] ?? 0);
                if ($already > 0) {
                    $refundPoolByMethod[$method] -= $already;
                    $paymentCents -= $already;
                }
                if ($paymentCents <= 0) {
                    continue;
                }

                SalePayment::query()->create([
                    'sale_id' => $locked->id,
                    'cash_session_id' => $sessionId ?? $payment->cash_session_id,
                    'type' => 'refund',
                    'method' => $method,
                    'amount' => $paymentCents / 100,
                    'amount_tendered' => null,
                    'change_amount' => 0,
                    'recorded_by' => auth()->id(),
                    'notes' => 'Estorno do recebimento #'.$payment->id,
                ]);
            }

            $locked->unsetRelation('payments');
            $locked->load('payments');
            if ($locked->amountPaidCents() !== 0) {
                throw new \DomainException('Não foi possível estornar o valor líquido restante.');
            }
            if ($locked->payment_status !== Sale::PAYMENT_REFUNDED) {
                $locked->markPaymentStatus(Sale::PAYMENT_REFUNDED);
            }
        }, 3);
        $sale->refresh();
    }
}
