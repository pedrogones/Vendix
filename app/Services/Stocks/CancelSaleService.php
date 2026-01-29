<?php
namespace App\Services\Stocks;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class CancelSaleService
{
    public function cancel(Sale $sale): void
    {
        if ($sale->status !== 'confirmed') {
            throw new \DomainException('Apenas vendas confirmadas podem ser canceladas');
        }

        DB::transaction(function () use ($sale) {
            foreach ($sale->items as $item) {
                app(StockService::class)->in(
                    product: $item->product,
                    quantity: $item->quantity,
                    reason: 'sale_cancel',
                    reference: $sale
                );
            }

            $sale->update([
                'status' => 'canceled',
            ]);
        });
    }
}
