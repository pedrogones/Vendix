<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consultar pedido — {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light; }
        body {
            margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif;
            background: linear-gradient(180deg, #fff7ed 0%, #f8fafc 40%, #f8fafc 100%);
            color: #0f172a; min-height: 100vh;
        }
        .wrap { max-width: 520px; margin: 0 auto; padding: 32px 16px 48px; }
        h1 { margin: 0 0 8px; font-size: 1.5rem; letter-spacing: -.02em; }
        p.lead { margin: 0 0 20px; color: #64748b; line-height: 1.5; }
        form, .card {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
        }
        label { display: grid; gap: 6px; margin-bottom: 12px; font-size: .85rem; color: #475569; }
        input {
            min-height: 42px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 8px 12px;
            font: inherit; color: inherit; background: #f8fafc;
        }
        button {
            width: 100%; min-height: 44px; border: 0; border-radius: 10px; background: #b45309;
            color: #fff7ed; font: inherit; font-weight: 700; cursor: pointer;
        }
        .error {
            margin: 12px 0 0; padding: 10px 12px; border-radius: 10px; background: #fef2f2;
            color: #991b1b; font-size: .9rem;
        }
        .card { margin-top: 16px; }
        .card h2 { margin: 0 0 10px; font-size: 1.1rem; }
        .row { display: flex; justify-content: space-between; gap: 12px; padding: 6px 0; font-size: .9rem; border-bottom: 1px solid #f1f5f9; }
        .row:last-child { border-bottom: 0; }
        .muted { color: #64748b; }
        .items { margin: 12px 0 0; padding: 0; list-style: none; }
        .items li { padding: 4px 0; font-size: .9rem; }
        a.home { display: inline-block; margin-top: 18px; color: #b45309; font-weight: 650; }
    </style>
</head>
<body>
    <main class="wrap">
        <h1>Consultar pedido</h1>
        <p class="lead">Informe o número do pedido e o telefone usado na compra pelo chat.</p>

        <form method="post" action="{{ route('order-status.lookup') }}">
            @csrf
            <label>
                Número do pedido
                <input type="text" name="order_number" value="{{ old('order_number', $order_number ?? '') }}" placeholder="000123" required>
            </label>
            <label>
                Telefone com DDD
                <input type="text" name="phone" value="{{ old('phone', $phone ?? '') }}" placeholder="11999998888" required>
            </label>
            <button type="submit">Consultar</button>
            @error('order_number')<div class="error">{{ $message }}</div>@enderror
            @error('phone')<div class="error">{{ $message }}</div>@enderror
            @if (! empty($error))
                <div class="error">{{ $error }}</div>
            @endif
        </form>

        @if ($sale)
            <section class="card" aria-label="Status do pedido">
                <h2>{{ $sale->orderNumber() }}</h2>
                <div class="row"><span class="muted">Atendimento</span><strong>{{ \App\Models\Sale::FULFILLMENT_STATUS_LABELS[$sale->fulfillment_status] ?? $sale->fulfillment_status }}</strong></div>
                <div class="row"><span class="muted">Modalidade</span><strong>{{ \App\Models\Sale::FULFILLMENT_TYPE_LABELS[$sale->fulfillment_type] ?? $sale->fulfillment_type }}</strong></div>
                <div class="row"><span class="muted">Pagamento</span><strong>{{ \App\Models\Sale::PAYMENT_STATUS_LABELS[$sale->payment_status] ?? $sale->payment_status }}</strong></div>
                <div class="row"><span class="muted">Total</span><strong>R$ {{ number_format($sale->total, 2, ',', '.') }}</strong></div>
                @if ($sale->fulfillment_type === 'delivery')
                    <div class="row"><span class="muted">Endereço</span><strong>{{ \App\Http\Controllers\OrderStatusController::maskAddress($sale->delivery_address) }}</strong></div>
                @endif
                @if ($sale->needs_human_help)
                    <div class="row"><span class="muted">Atenção</span><strong>Loja avisada para atendimento humano</strong></div>
                @endif
                <ul class="items">
                    @foreach ($sale->items as $item)
                        <li>{{ $item->quantity }}x {{ $item->product?->name ?? 'Produto' }} — R$ {{ number_format($item->total_price, 2, ',', '.') }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <a class="home" href="{{ url('/') }}">Voltar ao início</a>
    </main>
</body>
</html>
