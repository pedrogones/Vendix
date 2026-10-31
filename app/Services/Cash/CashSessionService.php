<?php

namespace App\Services\Cash;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\SalePayment;
use App\Services\Products\ProductPricingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CashSessionService
{
    public function currentOpen(): ?CashSession
    {
        return CashSession::query()
            ->where('status', CashSession::STATUS_OPEN)
            ->orderByDesc('opened_at')
            ->orderByDesc('id')
            ->first();
    }

    public function requireOpen(): CashSession
    {
        $session = $this->currentOpen();
        if (! $session) {
            throw new \DomainException('Abra o caixa antes de continuar.');
        }

        return $session;
    }

    public function open(float|string $openingAmount, ?string $notes = null): CashSession
    {
        Gate::authorize('open-cash');
        $pricing = app(ProductPricingService::class);
        $openingCents = $pricing->cents($openingAmount);
        if ($openingCents < 0) {
            throw new \DomainException('Saldo inicial inválido.');
        }
        $notes = $this->normalizeNotes($notes);

        return DB::transaction(function () use ($openingCents, $notes): CashSession {
            CashSession::query()->where('status', CashSession::STATUS_OPEN)->lockForUpdate()->get();
            if ($this->currentOpen()) {
                throw new \DomainException('Já existe um caixa aberto. Feche-o antes de abrir outro.');
            }

            return CashSession::query()->create([
                'status' => CashSession::STATUS_OPEN,
                'opening_amount' => $openingCents / 100,
                'opened_by' => auth()->id(),
                'opened_at' => now(),
                'opening_notes' => $notes,
            ]);
        }, 3);
    }

    public function addMovement(CashSession $session, string $type, float|string $amount, string $reason): CashMovement
    {
        Gate::authorize('manage-cash-movements');
        if (! in_array($type, [CashMovement::TYPE_IN, CashMovement::TYPE_OUT], true)) {
            throw new \DomainException('Tipo de movimento de caixa inválido.');
        }
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 255) {
            throw new \DomainException('Informe o motivo (até 255 caracteres).');
        }
        $pricing = app(ProductPricingService::class);
        $amountCents = $pricing->cents($amount);
        if ($amountCents <= 0) {
            throw new \DomainException('Valor do movimento deve ser maior que zero.');
        }

        return DB::transaction(function () use ($session, $type, $amountCents, $reason): CashMovement {
            $locked = CashSession::query()->lockForUpdate()->findOrFail($session->id);
            if (! $locked->isOpen()) {
                throw new \DomainException('Só é possível movimentar um caixa aberto.');
            }

            return CashMovement::query()->create([
                'cash_session_id' => $locked->id,
                'type' => $type,
                'amount' => $amountCents / 100,
                'reason' => $reason,
                'recorded_by' => auth()->id(),
            ]);
        }, 3);
    }

    public function expectedCash(CashSession $session): float
    {
        $pricing = app(ProductPricingService::class);
        $opening = $pricing->cents($session->opening_amount);

        $payments = SalePayment::query()
            ->where('cash_session_id', $session->id)
            ->where('method', 'cash')
            ->get(['type', 'amount']);

        $cashIn = 0;
        $cashOut = 0;
        foreach ($payments as $payment) {
            $cents = $pricing->cents($payment->amount);
            if ($payment->type === 'payment') {
                $cashIn += $cents;
            } elseif ($payment->type === 'refund') {
                $cashOut += $cents;
            }
        }

        $movements = $session->movements()->get(['type', 'amount']);
        foreach ($movements as $movement) {
            $cents = $pricing->cents($movement->amount);
            if ($movement->type === CashMovement::TYPE_IN) {
                $cashIn += $cents;
            } else {
                $cashOut += $cents;
            }
        }

        return ($opening + $cashIn - $cashOut) / 100;
    }

    public function close(CashSession $session, float|string $countedCash, ?string $notes = null): CashSession
    {
        Gate::authorize('close-cash');
        $pricing = app(ProductPricingService::class);
        $countedCents = $pricing->cents($countedCash);
        if ($countedCents < 0) {
            throw new \DomainException('Valor contado inválido.');
        }
        $notes = $this->normalizeNotes($notes);

        return DB::transaction(function () use ($session, $countedCents, $notes, $pricing): CashSession {
            $locked = CashSession::query()->lockForUpdate()->with('movements')->findOrFail($session->id);
            if (! $locked->isOpen()) {
                throw new \DomainException('Este caixa já está fechado.');
            }
            $expectedCents = $pricing->cents($this->expectedCash($locked));
            $locked->forceFill([
                'status' => CashSession::STATUS_CLOSED,
                'expected_cash' => $expectedCents / 100,
                'counted_cash' => $countedCents / 100,
                'difference' => ($countedCents - $expectedCents) / 100,
                'closed_by' => auth()->id(),
                'closed_at' => now(),
                'closing_notes' => $notes,
            ])->save();

            return $locked->refresh();
        }, 3);
    }

    public function summary(CashSession $session): array
    {
        $pricing = app(ProductPricingService::class);
        $session->loadMissing('movements', 'opener', 'closer');

        $cashPayments = SalePayment::query()
            ->where('cash_session_id', $session->id)
            ->where('method', 'cash')
            ->get(['type', 'amount']);

        $paid = 0;
        $refunded = 0;
        foreach ($cashPayments as $payment) {
            $cents = $pricing->cents($payment->amount);
            if ($payment->type === 'payment') {
                $paid += $cents;
            } else {
                $refunded += $cents;
            }
        }

        $supply = 0;
        $withdraw = 0;
        foreach ($session->movements as $movement) {
            $cents = $pricing->cents($movement->amount);
            if ($movement->type === CashMovement::TYPE_IN) {
                $supply += $cents;
            } else {
                $withdraw += $cents;
            }
        }

        $expected = $this->expectedCash($session);

        return [
            'opening_amount' => (float) $session->opening_amount,
            'cash_payments' => $paid / 100,
            'cash_refunds' => $refunded / 100,
            'supply' => $supply / 100,
            'withdraw' => $withdraw / 100,
            'expected_cash' => $expected,
            'counted_cash' => $session->counted_cash !== null ? (float) $session->counted_cash : null,
            'difference' => $session->difference !== null ? (float) $session->difference : null,
            'status' => $session->status,
        ];
    }

    private function normalizeNotes(?string $notes): ?string
    {
        $notes = trim((string) $notes);
        if ($notes === '') {
            return null;
        }
        if (mb_strlen($notes) > 255) {
            throw new \DomainException('Observação deve ter até 255 caracteres.');
        }

        return $notes;
    }
}
