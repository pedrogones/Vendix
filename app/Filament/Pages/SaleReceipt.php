<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
class SaleReceipt extends Page
{
    protected static ?string $title = 'Comprovante';

    protected static ?string $navigationLabel = 'Comprovante';

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|null|\UnitEnum $navigationGroup = 'Vendas';

    protected static ?string $slug = 'vendas/{sale}/comprovante';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.sale-receipt';

    public Sale $sale;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view-sales') ?? false;
    }

    public function mount(Sale|int|string $sale): void
    {
        $resolved = $sale instanceof Sale
            ? $sale
            : Sale::query()->findOrFail($sale);

        if (! in_array($resolved->status, ['confirmed', 'canceled'], true)) {
            abort(404, 'Comprovante disponível apenas para vendas confirmadas ou canceladas.');
        }

        $this->sale = $resolved->load([
            'items.product',
            'payments',
            'returns.items.product',
            'exchanges.items.product',
            'user',
            'client',
        ]);
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Comprovante #'.str_pad((string) $this->sale->id, 4, '0', STR_PAD_LEFT);
    }

    public function downloadPdf()
    {
        $pdf = Pdf::loadView('sales.receipt-pdf', [
            'sale' => $this->sale,
            'company' => config('app.company'),
        ])->setPaper('a4', 'portrait');

        $filename = 'comprovante-venda-'.$this->sale->id.'-'.Carbon::now()->format('Ymd_His').'.pdf';

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $filename);
    }
}
