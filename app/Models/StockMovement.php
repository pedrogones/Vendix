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
   ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


}
