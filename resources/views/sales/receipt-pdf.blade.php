<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Comprovante venda #{{ $sale->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 24px;
        }
        .receipt-header { display: table; width: 100%; margin-bottom: 12px; }
        .receipt-company, .receipt-meta { display: table-cell; vertical-align: top; }
        .receipt-meta { text-align: right; }
        .receipt-company h1 { margin: 0 0 4px; font-size: 18px; }
        .muted { color: #6b7280; margin: 0; }
        .receipt-disclaimer {
            border: 1px solid #f59e0b;
            background: #fffbeb;
            padding: 8px 10px;
            margin: 0 0 14px;
            font-size: 11px;
        }
        .receipt-section { margin-bottom: 14px; }
        .receipt-section h2 { margin: 0 0 8px; font-size: 13px; }
        .receipt-sub { margin: 12px 0 6px; font-size: 12px; }
        .receipt-dl { margin: 0; }
        .receipt-dl > div { display: table; width: 100%; margin-bottom: 4px; }
        .receipt-dl dt, .receipt-dl dd { display: table-cell; vertical-align: top; }
        .receipt-dl dt { width: 30%; color: #6b7280; }
        .receipt-dl dd { margin: 0; text-align: left; word-wrap: break-word; }
        .receipt-origin-tag {
            display: inline-block; margin-top: 6px; padding: 2px 8px;
            border: 1px solid #f59e0b; color: #b45309; font-size: 10px; font-weight: 700;
        }
        .receipt-table { width: 100%; border-collapse: collapse; }
        .receipt-table th, .receipt-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 6px 4px;
            text-align: left;
        }
        .receipt-table .num, .receipt-table th.num, .receipt-table td.num { text-align: right; }
        .receipt-table tfoot th { border-top: 1px solid #111827; border-bottom: 0; padding-top: 8px; }
        .receipt-change { margin: 8px 0 0; text-align: right; }
    </style>
</head>
<body>
    @include('sales.partials.receipt-content', ['sale' => $sale, 'company' => $company])
</body>
</html>
