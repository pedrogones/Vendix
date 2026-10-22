<?php

namespace App\Models;

use Carbon\Traits\Timestamp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use Timestamp;

    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'reason',
        'reference_id',
        'reference_type',
        'reference',
        'user_id',
        'created_at',
        'updated_at',
        'compensates_id',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \DomainException('Movimentos não podem ser editados. Registre uma compensação.'));
        static::deleting(fn () => throw new \DomainException('Movimentos não podem ser excluídos. Registre uma compensação.'));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
