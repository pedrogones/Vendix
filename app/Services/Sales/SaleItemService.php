<?php

namespace App\Services\Sales;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Products\ProductPricingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SaleItemService
{
    public function add(Sale $sale, int $productId, int $quantity, float $discount = 0): SaleItem
    {
        return $this->write($sale, $productId, $quantity, $discount);
    }

    public function edit(Sale $sale, SaleItem $item, int $quantity, float $discount = 0): SaleItem
    {
        return $this->write($sale, (int) $item->product_id, $quantity, $discount, $item);
    }

    public function remove(Sale $sale, int $itemId): void
    {
        DB::transaction(function () use ($sale, $itemId): void {
            $locked = $this->lockDraft($sale);
            $locked->items()->findOrFail($itemId)->delete();
        });
        $sale->refresh();
    }

    private function write(Sale $sale, int $productId, int $quantity, float $discount, ?SaleItem $item = null): SaleItem
    {
        return DB::transaction(function () use ($sale, $productId, $quantity, $discount, $item): SaleItem {
            $locked = $this->lockDraft($sale);
            $product = Product::query()->lockForUpdate()->findOrFail($productId);
            if (! $product->is_active) {
                throw new \DomainException('Produto indisponível.');
            }
            $alreadyAdded = (int) $locked->items()->where('product_id', $productId)
                ->when($item, fn ($query) => $query->where('id', '!=', $item->id))->sum('quantity');
            if ($quantity <= 0 || $product->stock < $alreadyAdded + $quantity) {
                throw new \DomainException('Quantidade inválida ou estoque insuficiente para os itens da venda.');
            }
            $record = $item ? $locked->items()->findOrFail($item->id) : new SaleItem(['sale_id' => $locked->id, 'product_id' => $productId]);
            $price = $item ? $record->unit_price : $product->final_price;
            $record->fill([
                'quantity' => $quantity,
                'unit_price' => $price,
                'discount' => $discount,
                'total_price' => app(ProductPricingService::class)->lineTotal($quantity, $price, $discount),
            ])->save();
            $sale->refresh();

            return $record;
        });
    }

    private function lockDraft(Sale $sale): Sale
    {
        $locked = Sale::query()->lockForUpdate()->findOrFail($sale->id);
        Gate::authorize($locked->user_id === auth()->id() ? 'create-sales' : 'edit-sales');
        if ($locked->status !== 'draft') {
            throw new \DomainException('Apenas rascunhos aceitam alteração de itens.');
        }

        return $locked;
    }
}
