<?php

namespace App\Models;

use Carbon\Traits\Timestamp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use Timestamp;

    protected $table = 'sale_items';
    protected $fillable = ['sale_id', 'product_id', 'quantity', 'unit_price', 'total_price', 'discount'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

}
