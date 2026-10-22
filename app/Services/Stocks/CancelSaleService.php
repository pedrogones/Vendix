<?php

namespace App\Services\Stocks;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CancelSaleService
{
    public function cancel(Sale $sale, string $reason): void
    {
        Gate::authorize('cancel-sales');
        if (trim($reason) === '' || mb_strlen($reason) > 255) {
            throw new \DomainException('Informe o motivo do cancelamento (até 255 caracteres).');
        }
        DB::transaction(function () use ($sale, $reason): void {
            $locked = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            if ($locked->status !== 'confirmed') {
                throw new \DomainException('Apenas vendas confirmadas podem ser canceladas.');
            }
            foreach ($locked->items()->orderBy('product_id')->orderBy('id')->get() as $item) {
                $product = Product::query()->lockForUpdate()->find($item->product_id);
                if (! $product) {
                    throw new \DomainException('Produto da venda não encontrado para devolução.');
                }
                app(StockService::class)->in($product, (int) $item->quantity, 'return', $locked, mb_substr('Cancelamento: '.$reason, 0, 255));
            }
            $locked->transitionTo('canceled', [
                'cancellation_reason' => $reason,
                'canceled_by' => auth()->id(),
                'canceled_at' => now(),
            ]);
        }, 3);
        $sale->refresh();
    }
}
