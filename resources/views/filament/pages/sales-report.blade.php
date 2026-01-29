<x-filament-panels::page>
    @once
        <style>
            .report-summary {
                display: grid;
                gap: 14px;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .summary-card {
                border-radius: 16px;
                padding: 16px;
                border: 1px solid rgba(15, 23, 42, 0.12);
                background: linear-gradient(135deg, rgba(248, 250, 252, 0.9), rgba(255, 255, 255, 0.9));
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            }

            .dark .summary-card {
                background: rgba(255, 255, 255, 0.04);
                border-color: rgba(255, 255, 255, 0.08);
                box-shadow: none;
            }

            .summary-card .label {
                font-size: 11px;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: #64748b;
                font-weight: 600;
            }

            .dark .summary-card .label {
                color: #cbd5f5;
            }

            .summary-card .value {
                margin-top: 6px;
                font-size: 20px;
                font-weight: 700;
                color: #0f172a;
            }

            .dark .summary-card .value {
                color: #f8fafc;
            }

            .summary-card .hint {
                margin-top: 6px;
                font-size: 12px;
                color: #64748b;
            }

            .dark .summary-card .hint {
                color: #94a3b8;
            }

            .summary-pill {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 6px 12px;
                border-radius: 999px;
                border: 1px solid rgba(15, 23, 42, 0.12);
                background: rgba(248, 250, 252, 0.7);
                font-size: 12px;
                color: #64748b;
                font-weight: 500;
            }

            .dark .summary-pill {
                border-color: rgba(255, 255, 255, 0.08);
                background: rgba(255, 255, 255, 0.04);
                color: #cbd5f5;
            }

            .summary-pill span {
                width: 6px;
                height: 6px;
                border-radius: 999px;
                background: #10b981;
            }

            .report-table-wrap {
                border-radius: 18px;
                border: 1px solid rgba(15, 23, 42, 0.12);
                background: #fff;
                overflow: hidden;
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            }

            .dark .report-table-wrap {
                background: rgba(255, 255, 255, 0.03);
                border-color: rgba(255, 255, 255, 0.08);
                box-shadow: none;
            }

            .report-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 13px;
            }

            .report-table thead th {
                text-align: left;
                padding: 14px 16px;
                font-size: 11px;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: #64748b;
                background: linear-gradient(180deg, rgba(248, 250, 252, 0.9), rgba(241, 245, 249, 0.9));
                border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            }

            .dark .report-table thead th {
                color: #cbd5f5;
                background: rgba(255, 255, 255, 0.04);
                border-bottom-color: rgba(255, 255, 255, 0.06);
            }

            .report-table tbody td {
                padding: 14px 16px;
                border-bottom: 1px solid rgba(15, 23, 42, 0.06);
                color: #0f172a;
            }

            .dark .report-table tbody td {
                color: #e2e8f0;
                border-bottom-color: rgba(255, 255, 255, 0.06);
            }

            .report-table tbody tr:nth-child(even) td {
                background: rgba(248, 250, 252, 0.6);
            }

            .dark .report-table tbody tr:nth-child(even) td {
                background: rgba(255, 255, 255, 0.02);
            }

            .report-table tbody tr:hover td {
                background: rgba(251, 191, 36, 0.08);
            }

            .dark .report-table tbody tr:hover td {
                background: rgba(251, 191, 36, 0.12);
            }

            .report-table .money {
                font-weight: 600;
                color: #0f172a;
            }

            .dark .report-table .money {
                color: #f8fafc;
            }

            .report-table .right {
                text-align: right;
            }
        </style>
    @endonce

    <div class="space-y-6">
        <x-filament::section heading="Filtros">
            <div class="space-y-4">
                {{ $this->form }}

                <div class="flex flex-wrap gap-2" style="margin-top: .75rem">
                    <x-filament::button wire:click="applyFilters" color="primary">
                        Gerar relatório
                    </x-filament::button>
                    <x-filament::button wire:click="clearFilters" outlined color="gray">
                        Limpar filtros
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section style="margin-top: .75rem" heading="Resumo">
            @php
                $totals = $report['totals'] ?? ['sales' => 0, 'items' => 0, 'discount' => 0, 'revenue' => 0, 'ticket_medio' => 0];
                $filters = $report['filters'] ?? [];
                $start = $filters['start_date']?->format('d/m/Y');
                $end = $filters['end_date']?->format('d/m/Y');
                $periodLabel = ($start || $end) ? (($start ?? '...') . ' a ' . ($end ?? '...')) : 'Todo período';
                $statusLabel = $filters['status']
                    ? ($report['status_labels'][$filters['status']] ?? $filters['status'])
                    : 'Todos';
            @endphp

            <div class="flex flex-wrap gap-2  text-xs" style="margin-bottom: .75rem" >
                <span class="summary-pill"><span></span>Período: {{ $periodLabel }}</span>
                <span class="summary-pill"><span style="background:#f59e0b"></span>Status: {{ $statusLabel }}</span>
            </div>

            <div class="mt-4 report-summary">
                <div class="summary-card">
                    <div class="label">Vendas</div>
                    <div class="value">{{ $totals['sales'] }}</div>
                    <div class="hint">Quantidade de pedidos</div>
                </div>
                <div class="summary-card">
                    <div class="label">Itens</div>
                    <div class="value">{{ $totals['items'] }}</div>
                    <div class="hint">Itens vendidos no período</div>
                </div>
                <div class="summary-card">
                    <div class="label">Descontos</div>
                    <div class="value">R$ {{ number_format($totals['discount'], 2, ',', '.') }}</div>
                    <div class="hint">Total aplicado</div>
                </div>
                <div class="summary-card">
                    <div class="label">Faturamento</div>
                    <div class="value">R$ {{ number_format($totals['revenue'], 2, ',', '.') }}</div>
                    <div class="hint">Receita confirmada</div>
                </div>
                <div class="summary-card">
                    <div class="label">Ticket médio</div>
                    <div class="value">R$ {{ number_format($totals['ticket_medio'], 2, ',', '.') }}</div>
                    <div class="hint">Média por venda</div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section style="margin-top: .75rem" heading="Vendas">
            @if (!empty($report['sales']) && $report['sales']->count())
                @php
                    $statusClasses = [
                        'confirmed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200',
                        'draft' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200',
                        'canceled' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-200',
                    ];
                    $displayTotal = $report['sales']->sum('total');
                @endphp

                <div class="flex flex-wrap items-center justify-between gap-3 pb-3">
                    <div>
                        <div class="text-sm font-semibold text-gray-800 dark:text-gray-200">Lista de vendas</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $report['sales']->count() }} vendas exibidas
                        </div>
                    </div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-gray-200/80 bg-white/70 px-3 py-1 text-xs text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
                        Total exibido: <span class="font-semibold text-gray-900 dark:text-white">R$ {{ number_format($displayTotal, 2, ',', '.') }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto report-table-wrap">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Venda</th>
                                <th>Cliente</th>
                                <th>Vendedor</th>
                                <th class="right">Itens</th>
                                <th class="right">Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['sales'] as $sale)
                                <tr>
                                    <td>
                                        {{ $sale->created_at?->format('d/m/Y H:i') ?? '-' }}
                                    </td>
                                    <td>#{{ $sale->id }}</td>
                                    <td>
                                        {{ $sale->client?->cpf ?? '-' }}
                                    </td>
                                    <td>
                                        {{ $sale->user?->name ?? '-' }}
                                    </td>
                                    <td class="right">
                                        {{ $sale->items->sum('quantity') }}
                                    </td>
                                    <td class="right money">
                                        R$ {{ number_format($sale->total, 2, ',', '.') }}
                                    </td>
                                    <td>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$sale->status] ?? 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-200' }}">
                                            {{ $report['status_labels'][$sale->status] ?? $sale->status }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $report['sales']->links('pagination::tailwind') }}
                </div>
            @else
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm text-gray-500">
                    Nenhuma venda encontrada para os filtros selecionados.
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
