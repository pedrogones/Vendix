<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório de Vendas</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 24px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 16px;
        }
        .header .company {
            display: table-cell;
            vertical-align: top;
        }
        .header .report {
            display: table-cell;
            vertical-align: top;
            text-align: right;
        }
        .company h1 {
            margin: 0 0 4px 0;
            font-size: 18px;
        }
        .muted { color: #6b7280; }
        .meta { margin-top: 4px; }
        .section-title {
            font-size: 13px;
            margin: 18px 0 8px;
            font-weight: 700;
        }
        .summary {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 12px;
        }
        .summary table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary td {
            padding: 6px 4px;
            vertical-align: top;
        }
        .summary .value {
            font-weight: 700;
        }
        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .report-table th, .report-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 8px 6px;
            text-align: left;
        }
        .report-table th {
            background: #f9fafb;
            font-weight: 700;
            color: #374151;
        }
        .text-right { text-align: right; }
        .footer {
            margin-top: 18px;
            font-size: 11px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    @php
        $totals = $report['totals'] ?? ['sales' => 0, 'items' => 0, 'discount' => 0, 'revenue' => 0, 'ticket_medio' => 0];
        $filters = $report['filters'] ?? [];
        $statusLabels = $report['status_labels'] ?? [];
        $periodo = 'Todos';

        if (!empty($filters['start_date']) || !empty($filters['end_date'])) {
            $inicio = $filters['start_date']?->format('d/m/Y') ?? '...';
            $fim = $filters['end_date']?->format('d/m/Y') ?? '...';
            $periodo = $inicio . ' até ' . $fim;
        }

        $status = $filters['status'] ? ($statusLabels[$filters['status']] ?? $filters['status']) : 'Todos';
    @endphp

    <div class="header">
        <div class="company">
            <h1>{{ $company['name'] ?? config('app.name') }}</h1>
            @if (!empty($company['cnpj']))
                <div class="meta">CNPJ: {{ $company['cnpj'] }}</div>
            @endif
            @if (!empty($company['address']))
                <div class="meta">{{ $company['address'] }}</div>
            @endif
            @if (!empty($company['phone']))
                <div class="meta">Telefone: {{ $company['phone'] }}</div>
            @endif
        </div>
        <div class="report">
            <div class="section-title">Relatório de Vendas</div>
            <div class="muted">Gerado em {{ $generatedAt->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="summary">
        <table>
            <tr>
                <td>
                    <div class="muted">Período</div>
                    <div class="value">{{ $periodo }}</div>
                </td>
                <td>
                    <div class="muted">Status</div>
                    <div class="value">{{ $status }}</div>
                </td>
                <td>
                    <div class="muted">Vendas</div>
                    <div class="value">{{ $totals['sales'] }}</div>
                </td>
                <td>
                    <div class="muted">Itens</div>
                    <div class="value">{{ $totals['items'] }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="muted">Faturamento</div>
                    <div class="value">R$ {{ number_format($totals['revenue'], 2, ',', '.') }}</div>
                </td>
                <td>
                    <div class="muted">Descontos</div>
                    <div class="value">R$ {{ number_format($totals['discount'], 2, ',', '.') }}</div>
                </td>
                <td>
                    <div class="muted">Ticket médio</div>
                    <div class="value">R$ {{ number_format($totals['ticket_medio'], 2, ',', '.') }}</div>
                </td>
                <td></td>
            </tr>
        </table>
    </div>

    <div class="section-title">Detalhamento</div>
    <table class="report-table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Venda</th>
                <th>Cliente</th>
                <th>Vendedor</th>
                <th class="text-right">Itens</th>
                <th class="text-right">Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['sales'] as $sale)
                <tr>
                    <td>{{ $sale->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>#{{ $sale->id }}</td>
                    <td>{{ $sale->client?->cpf ?? '-' }}</td>
                    <td>{{ $sale->user?->name ?? '-' }}</td>
                    <td class="text-right">{{ $sale->items->sum('quantity') }}</td>
                    <td class="text-right">R$ {{ number_format($sale->total, 2, ',', '.') }}</td>
                    <td>{{ $statusLabels[$sale->status] ?? $sale->status }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted">Nenhuma venda encontrada para os filtros selecionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Relatório gerado automaticamente pelo sistema.
    </div>
</body>
</html>
