<x-filament-panels::page>
    @php
        $orders = $this->orders;
        $canManage = auth()->user()?->can('manage-chat-orders');
    @endphp

    @once
        <style>
            .co { color: #18181b; font-size: 14px; }
            .dark .co { color: #fafafa; }
            .co-filters { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px; }
            .co-chip {
                min-height:34px; padding:0 12px; border-radius:999px; border:1px solid #e4e4e7;
                background:#fff; font:inherit; font-size:.8rem; font-weight:650; cursor:pointer;
            }
            .dark .co-chip { background:#18181b; border-color:#3f3f46; color:#fafafa; }
            .co-chip.active { background:#b45309; border-color:transparent; color:#fff7ed; }
            .co-list { display:grid; gap:10px; }
            .co-card {
                border:1px solid #e4e4e7; border-radius:12px; background:#fff; padding:14px 16px;
                display:grid; gap:10px;
            }
            .dark .co-card { background:#18181b; border-color:#3f3f46; }
            .co-head { display:flex; flex-wrap:wrap; justify-content:space-between; gap:8px; }
            .co-head strong { font-size:.95rem; }
            .co-meta { color:#71717a; font-size:.8rem; }
            .dark .co-meta { color:#a1a1aa; }
            .co-badge {
                display:inline-flex; align-items:center; min-height:24px; padding:0 8px; border-radius:999px;
                background:#fff7ed; color:#b45309; font-size:.7rem; font-weight:650;
            }
            .co-badge.help { background:#fef2f2; color:#b91c1c; }
            .co-actions { display:flex; flex-wrap:wrap; gap:8px; }
            .co-btn {
                min-height:34px; padding:0 12px; border-radius:8px; border:1px solid #e4e4e7;
                background:#f4f4f5; font:inherit; font-size:.8rem; font-weight:650; cursor:pointer;
            }
            .dark .co-btn { background:#27272a; border-color:#3f3f46; color:#fafafa; }
            .co-btn.primary { background:#b45309; border-color:transparent; color:#fff7ed; }
            .co-empty { padding:24px; text-align:center; color:#71717a; }
            .co-note { font-size:.8rem; color:#71717a; }
            .dark .co-note { color:#a1a1aa; }
        </style>
    @endonce

    <div class="co">
        <div class="co-filters">
            <button type="button" class="co-chip {{ $this->filter === 'open' ? 'active' : '' }}" wire:click="setFilter('open')">Abertos</button>
            <button type="button" class="co-chip {{ $this->filter === 'help' ? 'active' : '' }}" wire:click="setFilter('help')">Precisam de humano</button>
            <button type="button" class="co-chip {{ $this->filter === 'all' ? 'active' : '' }}" wire:click="setFilter('all')">Todos</button>
        </div>

        <div class="co-list">
            @forelse ($orders as $sale)
                <article class="co-card" wire:key="chat-order-{{ $sale->id }}">
                    <div class="co-head">
                        <div>
                            <strong>{{ $sale->orderNumber() }} · {{ $sale->buyer_name ?: 'Sem nome' }}</strong>
                            <div class="co-meta">
                                {{ $sale->buyer_phone ?: '—' }}
                                · {{ \App\Models\Sale::FULFILLMENT_TYPE_LABELS[$sale->fulfillment_type] ?? $sale->fulfillment_type }}
                                · {{ $sale->created_at?->format('d/m H:i') }}
                                · R$ {{ number_format($sale->total, 2, ',', '.') }}
                            </div>
                        </div>
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <span class="co-badge">{{ \App\Models\Sale::FULFILLMENT_STATUS_LABELS[$sale->fulfillment_status] ?? $sale->fulfillment_status }}</span>
                            @if ($sale->needs_human_help)
                                <span class="co-badge help">Ajuda humana</span>
                            @endif
                        </div>
                    </div>

                    <div class="co-meta">
                        @foreach ($sale->items as $item)
                            {{ $item->quantity }}x {{ $item->product?->name ?? 'Produto' }}@if (! $loop->last); @endif
                        @endforeach
                    </div>

                    @if ($sale->fulfillment_type === 'delivery' && $sale->delivery_address)
                        <div class="co-note">Entrega: {{ $sale->delivery_address }}</div>
                    @endif
                    @if ($sale->human_help_note)
                        <div class="co-note">{{ $sale->human_help_note }}</div>
                    @endif

                    @if ($canManage)
                        <div class="co-actions">
                            @if (! in_array($sale->fulfillment_status, ['completed', 'canceled'], true))
                                <button type="button" class="co-btn primary" wire:click="advanceStatus({{ $sale->id }})" wire:loading.attr="disabled">Avançar status</button>
                                <button type="button" class="co-btn" wire:click="cancelFulfillment({{ $sale->id }})" wire:confirm="Cancelar o atendimento deste pedido?">Cancelar atendimento</button>
                            @endif
                            @if ($sale->needs_human_help)
                                <button type="button" class="co-btn" wire:click="clearHumanHelp({{ $sale->id }})">Resolver handoff</button>
                            @endif
                        </div>
                    @endif
                </article>
            @empty
                <div class="co-empty">Nenhum pedido do chat neste filtro.</div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
