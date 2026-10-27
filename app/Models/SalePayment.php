<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    public const METHODS = ['cash', 'pix', 'card', 'other'];

    public const METHOD_LABELS = [
        'cash' => 'Dinheiro',
        'pix' => 'Pix',
        'card' => 'Cartão',
        'other' => 'Outro',
    ];

    protected $fillable = [
        'sale_id',
        'type',
        'method',
        'amount',
        'amount_tendered',
        'change_amount',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_tendered' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
