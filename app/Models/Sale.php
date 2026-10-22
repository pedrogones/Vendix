<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = ['client_id', 'user_id', 'total', 'status', 'origin', 'checkout_key'];

    protected $casts = ['total' => 'decimal:2', 'canceled_at' => 'datetime'];

    private bool $lifecycleChange = false;

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

    public function save(array $options = [])
    {
        return DB::transaction(function () use ($options) {
            if ($this->exists) {
                $current = static::withTrashed()->lockForUpdate()->findOrFail($this->getKey());
                if (! $this->lifecycleChange && ($current->status !== 'draft' || $this->isDirty('status'))) {
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
        });
    }
}
