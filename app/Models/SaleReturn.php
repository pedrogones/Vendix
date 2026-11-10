<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    public const DESTINATION_RESTOCK = 'restock';

    public const DESTINATION_LOSS = 'loss';

    public const DESTINATIONS = [
        self::DESTINATION_RESTOCK,
        self::DESTINATION_LOSS,
    ];

    public const DESTINATION_LABELS = [
        self::DESTINATION_RESTOCK => 'Voltar ao estoque',
        self::DESTINATION_LOSS => 'Perda / não repor',
    ];

    protected $fillable = [
        'sale_id',
        'destination',
        'reason',
        'recorded_by',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
