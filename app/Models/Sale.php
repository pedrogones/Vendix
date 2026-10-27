<?php

namespace App\Models;

use App\Services\Products\ProductPricingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    use SoftDeletes;

    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_REFUNDED = 'refunded';

    public const PAYMENT_STATUS_LABELS = [
        self::PAYMENT_UNPAID => 'Não pago',
        self::PAYMENT_PAID => 'Pago',
        self::PAYMENT_REFUNDED => 'Estornado',
    ];

    protected $fillable = ['client_id', 'user_id', 'total', 'status', 'origin', 'checkout_key', 'payment_status'];

    protected $casts = ['total' => 'decimal:2', 'canceled_at' => 'datetime'];

    private bool $lifecycleChange = false;

    private bool $paymentChange = false;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function amountPaidCents(): int
    {
        $pricing = app(ProductPricingService::class);
        $payments = $this->payments->where('type', 'payment')->sum(fn (SalePayment $p) => $pricing->cents($p->amount));
        $refunds = $this->payments->where('type', 'refund')->sum(fn (SalePayment $p) => $pricing->cents($p->amount));

        return (int) $payments - (int) $refunds;
    }

    public function transitionTo(string $status, array $attributes = []): void
    {
        if (($this->status === 'draft' && $status === 'confirmed') || ($this->status === 'confirmed' && $status === 'canceled')) {
            $this->lifecycleChange = true;
            try {
                $this->forceFill([...$attributes, 'status' => $status])->save();
            } finally {
                $this->lifecycleChange = false;
            }

            return;
        }
        throw new \DomainException('Transição de venda inválida.');
    }

    public function markPaymentStatus(string $paymentStatus, array $attributes = []): void
    {
        $allowed = [
            self::PAYMENT_UNPAID => [self::PAYMENT_PAID],
            self::PAYMENT_PAID => [self::PAYMENT_REFUNDED],
        ];
        $from = $this->payment_status ?: self::PAYMENT_UNPAID;
        if (! in_array($paymentStatus, $allowed[$from] ?? [], true)) {
            throw new \DomainException('Transição de pagamento inválida.');
        }
        $this->paymentChange = true;
        try {
            $this->forceFill([...$attributes, 'payment_status' => $paymentStatus])->save();
        } finally {
            $this->paymentChange = false;
        }
    }

    public function save(array $options = [])
    {
        return DB::transaction(function () use ($options) {
            if ($this->exists) {
                $current = static::withTrashed()->lockForUpdate()->findOrFail($this->getKey());
                if ($this->paymentChange) {
                    if ($current->payment_status !== $this->getRawOriginal('payment_status')) {
                        throw new \DomainException('A situação do pagamento mudou. Atualize antes de continuar.');
                    }
                    $dirty = collect($this->getDirty())->except(['payment_status', 'updated_at']);
                    if ($dirty->isNotEmpty()) {
                        throw new \DomainException('Na atualização de pagamento só o status de pagamento pode mudar.');
                    }
                } elseif (! $this->lifecycleChange && ($current->status !== 'draft' || $this->isDirty('status'))) {
                    throw new \DomainException('Vendas concluídas são imutáveis. Use a operação de cancelamento.');
                }
                if ($this->lifecycleChange && $current->status !== $this->getRawOriginal('status')) {
                    throw new \DomainException('A situação da venda mudou. Atualize antes de continuar.');
                }
            }

            return parent::save($options);
        });
    }

    public function delete()
    {
        return DB::transaction(function () {
            $current = static::withTrashed()->lockForUpdate()->findOrFail($this->getKey());
            if ($current->status !== 'draft') {
                throw new \DomainException('Apenas rascunhos podem ser excluídos.');
            }

            return parent::delete();
        });
    }

    protected static function booted(): void
    {
        static::creating(function (Sale $sale): void {
            $sale->status = 'draft';
            $sale->total = 0;
            $sale->payment_status = $sale->payment_status ?: self::PAYMENT_UNPAID;
        });
    }
}
