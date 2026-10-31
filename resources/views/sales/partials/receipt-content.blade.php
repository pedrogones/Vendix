@php
    $company = $company ?? config('app.company');
    $statusLabels = [
        'draft' => 'Rascunho',
        'confirmed' => 'Confirmada',
        'canceled' => 'Cancelada',
    ];
    $paymentStatusLabels = \App\Models\Sale::PAYMENT_STATUS_LABELS;
    $methodLabels = \App\Models\SalePayment::METHOD_LABELS;
    $payments = $sale->payments->where('type', 'payment');
    $refunds = $sale->payments->where('type', 'refund');
    $changeTotal = $payments->sum(fn ($p) => (float) $p->change_amount);
    $isChatOrder = ($sale->origin ?? '') === 'chatbot';
    $originLabel = match ($sale->origin) {
        'chatbot' => 'Pedido pelo chat',
        'pdv', 'balcao', 'counter' => 'Balcão (PDV)',
        default => filled($sale->origin) ? (string) $sale->origin : 'Balcão (PDV)',
    };
    $clientLabel = $sale->client?->cpf
        ? 'CPF '.$sale->client->cpf
        : (filled($sale->buyer_name) || filled($sale->buyer_phone)
            ? trim(($sale->buyer_name ?? '').(filled($sale->buyer_phone) ? ' · '.$sale->buyer_phone : ''))
            : 'Não identificado');
@endphp

<header class="receipt-header">
    <div class="receipt-company">
        <h1>{{ $company['name'] ?? config('app.name') }}</h1>
        @if (! empty($company['cnpj']))
            <p class="muted">CNPJ {{ $company['cnpj'] }}</p>
        @endif
        @if (! empty($company['address']))
            <p class="muted">{{ $company['address'] }}</p>
        @endif
        @if (! empty($company['phone']))
            <p class="muted">{{ $company['phone'] }}</p>
        @endif
    </div>
    <div class="receipt-meta">
        <strong>{{ $isChatOrder ? 'Comprovante — pedido do chat' : 'Comprovante de venda' }}</strong>
        <p>{{ $isChatOrder ? 'Pedido' : 'Venda' }} #{{ str_pad((string) $sale->id, 4, '0', STR_PAD_LEFT) }}</p>
        <p class="muted">{{ ($sale->canceled_at ?? $sale->updated_at ?? $sale->created_at)?->format('d/m/Y H:i') }}</p>
        @if ($isChatOrder)
            <p class="receipt-origin-tag">Origem: chat / assistente</p>
        @endif
    </div>
</header>

<p class="receipt-disclaimer">
    Este comprovante é apenas um registro operacional interno. <strong>Não é documento fiscal</strong>
    (não substitui NFC-e, cupom fiscal ou nota).
</p>

<section class="receipt-section">
    <h2>Resumo</h2>
    <dl class="receipt-dl">
        <div><dt>Status</dt><dd>{{ $statusLabels[$sale->status] ?? $sale->status }}</dd></div>
        <div><dt>Pagamento</dt><dd>{{ $paymentStatusLabels[$sale->payment_status] ?? $sale->payment_status }}</dd></div>
        <div><dt>Operador</dt><dd>{{ $sale->user?->name ?? ($isChatOrder ? 'Assistente (chat)' : '—') }}</dd></div>
        <div><dt>Cliente</dt><dd>{{ $clientLabel }}</dd></div>
        <div><dt>Origem</dt><dd>{{ $originLabel }}</dd></div>
        @if ($isChatOrder && filled($sale->fulfillment_type))
            <div><dt>Modalidade</dt><dd>{{ \App\Models\Sale::FULFILLMENT_TYPE_LABELS[$sale->fulfillment_type] ?? $sale->fulfillment_type }}</dd></div>
        @endif
        @if ($isChatOrder && filled($sale->fulfillment_status))
            <div><dt>Atendimento</dt><dd>{{ \App\Models\Sale::FULFILLMENT_STATUS_LABELS[$sale->fulfillment_status] ?? $sale->fulfillment_status }}</dd></div>
        @endif
        @if ($isChatOrder && $sale->fulfillment_type === 'delivery' && filled($sale->delivery_address))
            <div><dt>Endereço</dt><dd>{{ $sale->delivery_address }}</dd></div>
        @endif
        @if ($sale->status === 'canceled')
            <div><dt>Cancelamento</dt><dd>{{ $sale->cancellation_reason ?: '—' }} · {{ $sale->canceled_at?->format('d/m/Y H:i') }}</dd></div>
        @endif
    </dl>
</section>

<section class="receipt-section">
    <h2>Itens</h2>
    <table class="receipt-table">
        <thead>
            <tr>
                <th>Produto</th>
                <th class="num">Qtd.</th>
                <th class="num">Unit.</th>
                <th class="num">Desc.</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sale->items as $item)
                <tr>
                    <td>{{ $item->product?->name ?? 'Produto removido' }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td class="num">R$ {{ number_format($item->discount, 2, ',', '.') }}</td>
                    <td class="num">R$ {{ number_format($item->total_price, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Sem itens.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4">Total</th>
                <th class="num">R$ {{ number_format($sale->total, 2, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>
</section>

<section class="receipt-section">
    <h2>Recebimento</h2>
    @if ($payments->isEmpty())
        <p class="muted">Nenhum recebimento registrado.</p>
    @else
        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Meio</th>
                    <th class="num">Valor</th>
                    <th class="num">Recebido</th>
                    <th class="num">Troco</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments as $payment)
                    <tr>
                        <td>{{ $methodLabels[$payment->method] ?? $payment->method }}</td>
                        <td class="num">R$ {{ number_format($payment->amount, 2, ',', '.') }}</td>
                        <td class="num">
                            @if ($payment->amount_tendered !== null)
                                R$ {{ number_format($payment->amount_tendered, 2, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="num">
                            @if ((float) $payment->change_amount > 0)
                                R$ {{ number_format($payment->change_amount, 2, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($changeTotal > 0)
            <p class="receipt-change">Troco total: <strong>R$ {{ number_format($changeTotal, 2, ',', '.') }}</strong></p>
        @endif
    @endif

    @if ($refunds->isNotEmpty())
        <h3 class="receipt-sub">Estornos</h3>
        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Meio</th>
                    <th class="num">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($refunds as $refund)
                    <tr>
                        <td>{{ $methodLabels[$refund->method] ?? $refund->method }}</td>
                        <td class="num">R$ {{ number_format($refund->amount, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>
