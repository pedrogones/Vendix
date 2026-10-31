<x-filament-panels::page>
    @once
        <style>
            .sr-toolbar { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px; }
            .sr-panel {
                border: 1px solid #e4e4e7;
                border-radius: 12px;
                background: #fff;
                padding: 20px;
                color: #18181b;
            }
            .dark .sr-panel { border-color:#3f3f46; background:#18181b; color:#fafafa; }
            .sr-panel .receipt-header { display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:12px; }
            .sr-panel .receipt-company h1 { margin:0 0 4px; font-size:1.25rem; font-weight:650; }
            .sr-panel .muted { color:#71717a; margin:0; font-size:.85rem; }
            .dark .sr-panel .muted { color:#a1a1aa; }
            .sr-panel .receipt-disclaimer {
                border:1px solid #fcd34d; background:#fffbeb; color:#92400e;
                padding:10px 12px; border-radius:8px; margin:0 0 16px; font-size:.85rem; line-height:1.45;
            }
            .dark .sr-panel .receipt-disclaimer {
                border-color:rgba(251,191,36,.35); background:rgba(251,191,36,.12); color:#fbbf24;
            }
            .sr-panel .receipt-section { margin-bottom:18px; }
            .sr-panel .receipt-section h2 { margin:0 0 10px; font-size:.95rem; font-weight:650; }
            .sr-panel .receipt-sub { margin:14px 0 8px; font-size:.85rem; font-weight:650; }
            .sr-panel .receipt-dl { display:grid; gap:6px; margin:0; }
            .sr-panel .receipt-dl > div { display:flex; justify-content:space-between; gap:12px; font-size:.85rem; }
            .sr-panel .receipt-dl dt { color:#71717a; }
            .dark .sr-panel .receipt-dl dt { color:#a1a1aa; }
            .sr-panel .receipt-dl dd { margin:0; font-weight:550; text-align:right; }
            .sr-panel .receipt-table { width:100%; border-collapse:collapse; font-size:.85rem; }
            .sr-panel .receipt-table th, .sr-panel .receipt-table td {
                border-bottom:1px solid #e4e4e7; padding:8px 4px; text-align:left;
            }
            .dark .sr-panel .receipt-table th, .dark .sr-panel .receipt-table td { border-color:#3f3f46; }
            .sr-panel .receipt-table .num { text-align:right; font-variant-numeric:tabular-nums; }
            .sr-panel .receipt-table tfoot th { border-top:1px solid currentColor; border-bottom:0; padding-top:10px; }
            .sr-panel .receipt-change { margin:10px 0 0; text-align:right; font-size:.9rem; }
            @media print {
                .fi-topbar, .fi-sidebar, .fi-header, .sr-toolbar, .fi-page-header { display:none !important; }
                .sr-panel { border:0; padding:0; border-radius:0; }
                body { background:#fff !important; }
            }
        </style>
    @endonce

    <div class="sr-toolbar no-print">
        <x-filament::button color="gray" tag="button" onclick="window.print()">
            Imprimir
        </x-filament::button>
        <x-filament::button color="gray" wire:click="downloadPdf" wire:loading.attr="disabled">
            Baixar PDF
        </x-filament::button>
        <x-filament::button tag="a" :href="\App\Filament\Pages\StartSale::getUrl()" color="primary">
            Nova venda
        </x-filament::button>
    </div>

    <div class="sr-panel" id="sale-receipt">
        @include('sales.partials.receipt-content', [
            'sale' => $this->sale,
            'company' => config('app.company'),
        ])
    </div>
</x-filament-panels::page>
