<?php

namespace App\Services\Sales;

use App\Models\Sale;
use App\Services\Stocks\ConfirmSaleService;
use Illuminate\Support\Facades\DB;

class ConfirmSaleWithPaymentService
{
    public function confirm(Sale $sale, array $payments): void
    {
        DB::transaction(function () use ($sale, $payments): void {
            app(ConfirmSaleService::class)->confirm($sale);
            app(RecordSalePaymentService::class)->record($sale->fresh(), $payments);
        }, 3);
        $sale->refresh();
    }
}
