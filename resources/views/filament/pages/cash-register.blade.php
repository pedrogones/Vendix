<x-filament-panels::page>
    @php
        $session = $this->session;
        $summary = $this->summary ?? [];
        $recent = $summary['recent'] ?? collect();
    @endphp

    @once
        <style>
            .cr {
                --cr-surface: #fff;
                --cr-subtle: #f4f4f5;
                --cr-border: #e4e4e7;
                --cr-text: #18181b;
                --cr-muted: #71717a;
                --cr-accent: #b45309;
                --cr-accent-soft: #fff7ed;
                --cr-ok: #15803d;
                --cr-danger: #b91c1c;
                color: var(--cr-text);
                font-size: 14px;
                min-width: 0;
            }
            .dark .cr {
                --cr-surface: #18181b;
                --cr-subtle: #27272a;
                --cr-border: #3f3f46;
                --cr-text: #fafafa;
                --cr-muted: #a1a1aa;
                --cr-accent: #fbbf24;
                --cr-accent-soft: rgba(251,191,36,.12);
                --cr-ok: #4ade80;
                --cr-danger: #f87171;
            }
            .cr-head { display:flex; flex-wrap:wrap; justify-content:space-between; gap:12px; margin-bottom:20px; }
            .cr-head h1 { margin:0; font-size:clamp(1.35rem,3vw,1.75rem); font-weight:650; letter-spacing:-.02em; }
            .cr-head p { margin:6px 0 0; color:var(--cr-muted); max-width:36rem; line-height:1.5; }
            .cr-badge { display:inline-flex; align-items:center; min-height:28px; padding:0 10px; border-radius:999px; font-size:.75rem; font-weight:650; background:var(--cr-accent-soft); color:var(--cr-accent); }
            .cr-grid { display:grid; gap:12px; }
            @media (min-width:900px){ .cr-grid.two{ grid-template-columns:1.2fr .8fr; } .cr-kpis{ grid-template-columns:repeat(3,minmax(0,1fr)); } }
            .cr-kpis { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; margin-bottom:12px; }
            .cr-card, .cr-kpi { border:1px solid var(--cr-border); border-radius:12px; background:var(--cr-surface); padding:16px; min-width:0; }
            .cr-kpi .label { font-size:.7rem; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:var(--cr-muted); }
            .cr-kpi .value { margin-top:8px; font-size:clamp(1.1rem,2.4vw,1.4rem); font-weight:650; font-variant-numeric:tabular-nums; }
            .cr-card h2 { margin:0 0 14px; font-size:.95rem; font-weight:650; }
            .cr-form { display:grid; gap:10px; }
            .cr-form label { display:grid; gap:4px; font-size:.8rem; color:var(--cr-muted); }
            .cr-form input, .cr-form select, .cr-form textarea {
                width:100%; min-height:40px; border:1px solid var(--cr-border); border-radius:8px;
                background:var(--cr-subtle); color:var(--cr-text); padding:8px 10px; font:inherit;
            }
            .cr-form textarea { min-height:72px; resize:vertical; }
            .cr-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:4px; }
            .cr-btn {
                display:inline-flex; align-items:center; justify-content:center; min-height:40px; padding:0 14px;
                border-radius:8px; border:1px solid var(--cr-border); background:var(--cr-surface); color:var(--cr-text);
                font:inherit; font-size:.85rem; font-weight:650; cursor:pointer;
            }
            .cr-btn.primary { background:var(--cr-accent); border-color:transparent; color:#fff7ed; }
            .dark .cr-btn.primary { color:#18181b; }
            .cr-list { display:flex; flex-direction:column; gap:8px; }
            .cr-row { display:flex; justify-content:space-between; gap:12px; padding:10px 12px; border-radius:10px; background:var(--cr-subtle); }
            .cr-row strong { display:block; font-size:.85rem; }
            .cr-row span { display:block; margin-top:2px; font-size:.72rem; color:var(--cr-muted); }
            .cr-amount { font-weight:650; font-variant-numeric:tabular-nums; white-space:nowrap; }
            .cr-amount.out { color:var(--cr-danger); }
            .cr-amount.in { color:var(--cr-ok); }
            .cr-empty { padding:18px; text-align:center; color:var(--cr-muted); font-size:.85rem; }
            .cr-diff { font-weight:650; }
            .cr-diff.neg { color:var(--cr-danger); }
            .cr-diff.pos { color:var(--cr-ok); }
        </style>
    @endonce

    <div class="cr">
        <header class="cr-head">
            <div>
                <h1>Caixa</h1>
                <p>Abertura, sangria/suprimento e fechamento com conferência do dinheiro em gaveta. Pix e cartão não entram no saldo físico.</p>
            </div>
            @if ($session)
                <span class="cr-badge">Aberto desde {{ $session->opened_at?->format('d/m H:i') }}</span>
            @endif
        </header>

        @if ($session)
            <section class="cr-kpis" aria-label="Resumo do caixa">
                <article class="cr-kpi">
                    <div class="label">Saldo inicial</div>
                    <div class="value">R$ {{ number_format($summary['opening_amount'] ?? 0, 2, ',', '.') }}</div>
                </article>
                <article class="cr-kpi">
                    <div class="label">Dinheiro das vendas</div>
                    <div class="value">R$ {{ number_format(($summary['cash_payments'] ?? 0) - ($summary['cash_refunds'] ?? 0), 2, ',', '.') }}</div>
                </article>
                <article class="cr-kpi">
                    <div class="label">Esperado na gaveta</div>
                    <div class="value">R$ {{ number_format($summary['expected_cash'] ?? 0, 2, ',', '.') }}</div>
                </article>
            </section>

            <div class="cr-grid two">
                <section class="cr-card">
                    <h2>Movimentar caixa</h2>
                    <div class="cr-form">
                        <label>
                            Tipo
                            <select wire:model="movementType">
                                <option value="out">Sangria (retirada)</option>
                                <option value="in">Suprimento (entrada)</option>
                            </select>
                        </label>
                        <label>
                            Valor
                            <input type="number" step="0.01" min="0" wire:model="movementAmount" placeholder="0,00">
                        </label>
                        <label>
                            Motivo
                            <input type="text" maxlength="255" wire:model="movementReason" placeholder="Ex.: troco para o caixa">
                        </label>
                        <div class="cr-actions">
                            <button type="button" class="cr-btn primary" wire:click="addMovement" wire:loading.attr="disabled">Registrar</button>
                        </div>
                    </div>

                    <h2 style="margin-top:20px;">Movimentos desta sessão</h2>
                    <div class="cr-list">
                        @forelse ($session->movements as $movement)
                            <div class="cr-row">
                                <div>
                                    <strong>{{ \App\Models\CashMovement::TYPE_LABELS[$movement->type] ?? $movement->type }}</strong>
                                    <span>{{ $movement->reason }} · {{ $movement->created_at?->format('d/m H:i') }}</span>
                                </div>
                                <div class="cr-amount {{ $movement->type === 'out' ? 'out' : 'in' }}">
                                    {{ $movement->type === 'out' ? '−' : '+' }} R$ {{ number_format($movement->amount, 2, ',', '.') }}
                                </div>
                            </div>
                        @empty
                            <div class="cr-empty">Nenhuma sangria ou suprimento ainda.</div>
                        @endforelse
                    </div>
                </section>

                <section class="cr-card">
                    <h2>Fechar caixa</h2>
                    <div class="cr-form">
                        <label>
                            Valor contado na gaveta
                            <input type="number" step="0.01" min="0" wire:model="countedCash" placeholder="0,00">
                        </label>
                        <label>
                            Observação do fechamento
                            <textarea wire:model="closingNotes" maxlength="255" placeholder="Opcional"></textarea>
                        </label>
                        <p style="margin:0; color:var(--cr-muted); font-size:.8rem;">
                            Esperado: R$ {{ number_format($summary['expected_cash'] ?? 0, 2, ',', '.') }}
                            · Aberto por {{ $session->opener?->name ?? '—' }}
                        </p>
                        <div class="cr-actions">
                            <button
                                type="button"
                                class="cr-btn primary"
                                wire:click="closeCash"
                                wire:confirm="Fechar o caixa com o valor contado informado?"
                                wire:loading.attr="disabled"
                            >
                                Fechar caixa
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        @else
            <div class="cr-grid two">
                <section class="cr-card">
                    <h2>Abrir caixa</h2>
                    <div class="cr-form">
                        <label>
                            Saldo inicial em dinheiro
                            <input type="number" step="0.01" min="0" wire:model="openingAmount" placeholder="0,00">
                        </label>
                        <label>
                            Observação
                            <textarea wire:model="openingNotes" maxlength="255" placeholder="Opcional"></textarea>
                        </label>
                        <div class="cr-actions">
                            <button type="button" class="cr-btn primary" wire:click="openCash" wire:loading.attr="disabled">Abrir caixa</button>
                        </div>
                    </div>
                </section>

                <section class="cr-card">
                    <h2>Últimos fechamentos</h2>
                    <div class="cr-list">
                        @forelse ($recent as $item)
                            <div class="cr-row">
                                <div>
                                    <strong>#{{ $item->id }} · {{ $item->closed_at?->format('d/m/Y H:i') }}</strong>
                                    <span>
                                        Esperado R$ {{ number_format($item->expected_cash, 2, ',', '.') }}
                                        · Contado R$ {{ number_format($item->counted_cash, 2, ',', '.') }}
                                    </span>
                                </div>
                                <div class="cr-diff {{ (float) $item->difference < 0 ? 'neg' : ((float) $item->difference > 0 ? 'pos' : '') }}">
                                    {{ (float) $item->difference >= 0 ? '+' : '' }}{{ number_format($item->difference, 2, ',', '.') }}
                                </div>
                            </div>
                        @empty
                            <div class="cr-empty">Nenhum caixa fechado ainda.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        @endif
    </div>
</x-filament-panels::page>
