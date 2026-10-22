<?php

namespace App\Services\Stocks;

use App\Models\Product;
use App\Models\Sale;
use App\Services\Products\ProductPricingService;
use Illuminate\Support\Facades\DB;

class ConfirmSaleService
{
    public function confirm(Sale $sale): void
    {
        DB::transaction(function () use ($sale): void {
            $locked = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            if ($locked->status !== 'draft') {
                throw new \DomainException('Venda inválida para confirmação.');
            }
            $items = $locked->items()->orderBy('product_id')->orderBy('id')->get();
            if ($items->isEmpty()) {
                throw new \DomainException('A venda não possui itens para confirmar.');
            }
            $totalCents = 0;
            $pricing = app(ProductPricingService::class);
            foreach ($items as $item) {
                $product = Product::query()->lockForUpdate()->find($item->product_id);
                if (! $product || ! $product->is_active) {
                    throw new \DomainException('Produto inexistente ou inativo na venda.');
                }
                $lineTotal = $pricing->lineTotal((int) $item->quantity, $item->unit_price, $item->discount);
                if ($pricing->cents($lineTotal) !== $pricing->cents($item->total_price)) {
                    throw new \DomainException('Total de item inconsistente. Revise a venda antes de confirmar.');
                }
                app(StockService::class)->out($product, (int) $item->quantity, 'sale', $locked);
                $totalCents += $pricing->cents($lineTotal);
            }
            $locked->transitionTo('confirmed', ['total' => $totalCents / 100]);
        }, 3);
        $sale->refresh();
    }
}
