<?php

namespace App\Models;

use App\Services\Products\ProductPricingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class SaleItem extends Model
{
    protected $fillable = ['sale_id', 'product_id', 'quantity', 'unit_price', 'total_price', 'discount'];

    protected $casts = ['quantity' => 'integer', 'unit_price' => 'decimal:2', 'total_price' => 'decimal:2', 'discount' => 'decimal:2'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function save(array $options = [])
    {
        return DB::transaction(function () use ($options) {
            $sale = $this->lockDraft();
            if ($this->exists && ($this->isDirty('sale_id') || $this->isDirty('product_id'))) {
                throw new \DomainException('Remova o item e adicione o produto correto.');
            }
            if (filter_var($this->getAttributes()['quantity'] ?? null, FILTER_VALIDATE_INT) === false) {
                throw new \DomainException('Quantidade deve ser um número inteiro.');
            }
            $this->total_price = app(ProductPricingService::class)->lineTotal($this->quantity, $this->unit_price, $this->discount);
            $result = parent::save($options);
            $sale->update(['total' => $sale->items()->sum('total_price')]);

            return $result;
        });
    }

    public function delete()
    {
        return DB::transaction(function () {
            $sale = $this->lockDraft();
            $result = parent::delete();
            $sale->update(['total' => $sale->items()->sum('total_price')]);

            return $result;
        });
    }

    private function lockDraft(): Sale
    {
        $id = $this->exists ? $this->getRawOriginal('sale_id') : $this->sale_id;
        $sale = Sale::query()->lockForUpdate()->findOrFail($id);
        if ($sale->status !== 'draft') {
            throw new \DomainException('Apenas rascunhos aceitam alteração de itens.');
        }

        return $sale;
    }
}
