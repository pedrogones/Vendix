<?php

namespace App\Services\Stocks;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class ConfirmSaleService
{
    public function confirm(Sale $sale): void
    {
        if ($sale->status !== 'draft') {
            throw new \DomainException('Venda inválida para confirmação');
        }

        DB::transaction(function () use ($sale) {
            $sale->loadMissing('items.product');

            if ($sale->items->isEmpty()) {
                throw new \DomainException('A venda não possui itens para confirmar.');
            }

            foreach ($sale->items as $item) {
                app(StockService::class)->out(
                    product: $item->product,
                    quantity: $item->quantity,
                    reason: 'sale',
                    reference: $sale
                );
            }

            $total = (float) $sale->items()->sum('total_price');

            $sale->update([
                'status' => 'confirmed',
                'total' => $total,
            ]);
        });
    }
}
