<?php

namespace App\Models;

use App\Services\Products\ProductPricingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    public const FULFILLMENT_PICKUP = 'pickup';

    public const FULFILLMENT_DELIVERY = 'delivery';

    public const FULFILLMENT_TYPE_LABELS = [
        self::FULFILLMENT_PICKUP => 'Retirada',
        self::FULFILLMENT_DELIVERY => 'Entrega',
    ];

    public const FULFILLMENT_RECEIVED = 'received';

    public const FULFILLMENT_PREPARING = 'preparing';

    public const FULFILLMENT_READY = 'ready';

    public const FULFILLMENT_COMPLETED = 'completed';

    public const FULFILLMENT_CANCELED = 'canceled';

    public const FULFILLMENT_STATUS_LABELS = [
        self::FULFILLMENT_RECEIVED => 'Recebido pela loja',
        self::FULFILLMENT_PREPARING => 'Em preparo',
        self::FULFILLMENT_READY => 'Pronto',
        self::FULFILLMENT_COMPLETED => 'Concluído',
        self::FULFILLMENT_CANCELED => 'Atendimento cancelado',
    ];

    private const FULFILLMENT_FIELDS = [
        'buyer_name',
        'buyer_phone',
        'fulfillment_type',
        'delivery_address',
        'fulfillment_status',
        'needs_human_help',
        'human_help_note',
        'public_token',
    ];

    protected $fillable = [
        'client_id',
        'user_id',
        'buyer_name',
        'buyer_phone',
        'total',
        'status',
        'origin',
        'checkout_key',
        'payment_status',
        'fulfillment_type',
        'delivery_address',
        'fulfillment_status',
        'needs_human_help',
        'human_help_note',
        'public_token',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'canceled_at' => 'datetime',
        'needs_human_help' => 'boolean',
    ];

    private bool $lifecycleChange = false;

    private bool $paymentChange = false;

    private bool $fulfillmentChange = false;

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

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function exchanges(): HasMany
    {
        return $this->hasMany(SaleExchange::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function returnedQuantityForItem(SaleItem $item): int
    {
        if ($this->relationLoaded('returns')) {
            return (int) $this->returns
                ->flatMap(fn (SaleReturn $return) => $return->relationLoaded('items') ? $return->items : $return->items()->get())
                ->where('sale_item_id', $item->id)
                ->sum('quantity');
        }

        return (int) SaleReturnItem::query()
            ->where('sale_item_id', $item->id)
            ->sum('quantity');
    }

    public function remainingQuantityForItem(SaleItem $item): int
    {
        return max(0, (int) $item->quantity - $this->returnedQuantityForItem($item));
    }

    public function allItemsFullyReturned(): bool
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        if ($items->isEmpty()) {
            return false;
        }

        foreach ($items as $item) {
            if ($this->remainingQuantityForItem($item) > 0) {
                return false;
            }
        }

        return true;
    }

    public function orderNumber(): string
    {
        return '#'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function amountPaidCents(): int
    {
        $pricing = app(ProductPricingService::class);
        $payments = $this->payments->where('type', 'payment')->sum(fn (SalePayment $p) => $pricing->cents($p->amount));
        $refunds = $this->payments->where('type', 'refund')->sum(fn (SalePayment $p) => $pricing->cents($p->amount));

        return (int) $payments - (int) $refunds;
    }

    /**
     * Receita líquida (recebimentos − estornos) das vendas do query informado.
     */
    public static function sumNetReceived(Builder $salesQuery): float
    {
        $net = SalePayment::query()
            ->whereIn('sale_id', (clone $salesQuery)->select('sales.id'))
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'payment' THEN amount WHEN type = 'refund' THEN -amount ELSE 0 END), 0) as net")
            ->value('net');

        return (float) $net;
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

    /**
     * @param  array<string,mixed>  $attributes
     */
    public function updateFulfillment(array $attributes): void
    {
        $payload = collect($attributes)->only(self::FULFILLMENT_FIELDS)->all();
        if ($payload === []) {
            throw new \DomainException('Nenhum campo de atendimento informado.');
        }
        if (array_key_exists('fulfillment_type', $payload)
            && $payload['fulfillment_type'] !== null
            && ! array_key_exists($payload['fulfillment_type'], self::FULFILLMENT_TYPE_LABELS)) {
            throw new \DomainException('Tipo de atendimento inválido.');
        }
        if (array_key_exists('fulfillment_status', $payload)
            && $payload['fulfillment_status'] !== null
            && ! array_key_exists($payload['fulfillment_status'], self::FULFILLMENT_STATUS_LABELS)) {
            throw new \DomainException('Status de atendimento inválido.');
        }
        if (($payload['fulfillment_type'] ?? $this->fulfillment_type) === self::FULFILLMENT_DELIVERY) {
            $address = $payload['delivery_address'] ?? $this->delivery_address;
            if (trim((string) $address) === '') {
                throw new \DomainException('Informe o endereço de entrega.');
            }
        }

        $this->fulfillmentChange = true;
        try {
            $this->forceFill($payload)->save();
        } finally {
            $this->fulfillmentChange = false;
        }
    }

    public static function newPublicToken(): string
    {
        return Str::lower(Str::random(32));
    }

    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
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
                } elseif ($this->fulfillmentChange) {
                    $dirty = collect($this->getDirty())->except([...self::FULFILLMENT_FIELDS, 'updated_at']);
                    if ($dirty->isNotEmpty()) {
                        throw new \DomainException('Na atualização de atendimento só os campos de fulfillment/contato/handoff podem mudar.');
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
            $sale->needs_human_help = (bool) ($sale->needs_human_help ?? false);
        });
    }
}
