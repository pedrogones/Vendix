<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'sku',
        'barcode',
        'category_id',
        'price',
        'promotional_price',
        'is_on_sale',
        'stock',
        'min_stock',
        'is_active',
        'external_data',
        'image_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'promotional_price' => 'decimal:2',
        'is_on_sale' => 'boolean',
        'is_active' => 'boolean',
        'external_data' => 'array',
    ];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function image(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Archive::class, 'image_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(ProductFavorite::class);
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'product_favorites')
            ->withTimestamps();
    }
    public function getFinalPriceAttribute(): float
    {
        if ($this->is_on_sale && $this->promotional_price) {
            return (float) $this->promotional_price;
        }

        return (float) $this->price;
    }
    public function getUrlAttribute(): ?string
    {
        if (! $this->path) {
            return null;
        }

        return asset('storage/' . $this->path);
    }


    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

}
