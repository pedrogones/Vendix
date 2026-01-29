<?php

namespace App\Models;

use Carbon\Traits\Timestamp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes, Timestamp;
    protected $fillable =  ['client_id', 'user_id', 'total', 'status'];

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
    protected static function booted(): void
    {
        static::creating(function ($sale) {
            $sale->status = 'draft';
            $sale->total = 0;
        });
    }

}
