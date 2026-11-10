<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleExchange extends Model
{
    protected $fillable = [
        'sale_id',
        'sale_return_id',
        'reason',
        'credit_cents',
        'debit_cents',
        'settlement_cents',
        'recorded_by',
    ];

    protected $casts = [
        'credit_cents' => 'integer',
        'debit_cents' => 'integer',
        'settlement_cents' => 'integer',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class, 'sale_return_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleExchangeItem::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
