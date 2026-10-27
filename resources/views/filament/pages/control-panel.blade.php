<x-filament-panels::page>
    @php
        $kpis = $data['kpis'] ?? [];
        $trend = $data['trend'] ?? ['labels' => [], 'values' => []];
        $lowStock = $data['low_stock'] ?? [];
        $recentSales = $data['recent_sales'] ?? [];
        $updatedAt = $data['meta']['updated_at'] ?? '-';
        $userName = auth()->user()?->name ?? 'Usuário';
        $urls = $urls ?? [];
        $lowStockCount = (int) ($kpis['low_stock'] ?? 0);
        $todayCount = (int) ($kpis['today_count'] ?? 0);
    @endphp

    @once
        <style>
            .cp {
                --cp-bg: transparent;
                --cp-surface: #ffffff;
                --cp-subtle: #f4f4f5;
                --cp-border: #e4e4e7;
                --cp-text: #18181b;
                --cp-muted: #71717a;
                --cp-accent: #b45309;
                --cp-accent-soft: #fff7ed;
                --cp-ok: #15803d;
                --cp-warn: #b45309;
                --cp-danger: #b91c1c;
                --cp-danger-soft: #fef2f2;
                color: var(--cp-text);
                font-size: 14px;
                min-width: 0;
            }

            .dark .cp {
                --cp-surface: #18181b;
                --cp-subtle: #27272a;
                --cp-border: #3f3f46;
                --cp-text: #fafafa;
                --cp-muted: #a1a1aa;
                --cp-accent: #fbbf24;
                --cp-accent-soft: rgba(251, 191, 36, 0.12);
                --cp-ok: #4ade80;
                --cp-warn: #fbbf24;
                --cp-danger: #f87171;
                --cp-danger-soft: rgba(248, 113, 113, 0.12);
            }

            .cp *, .cp *::before, .cp *::after { box-sizing: border-box; }

            .cp-head {
                display: flex;
                flex-wrap: wrap;
                align-items: flex-end;
                justify-content: space-between;
                gap: 16px;
                margin-bottom: 20px;
            }

            .cp-head h1 {
                margin: 0;
                font-size: clamp(1.35rem, 3vw, 1.75rem);
                font-weight: 650;
                letter-spacing: -0.02em;
                line-height: 1.2;
            }

            .cp-head p {
                margin: 6px 0 0;
                color: var(--cp-muted);
                font-size: 0.9rem;
                max-width: 36rem;
                line-height: 1.5;
            }

            .cp-updated {
                color: var(--cp-muted);
                font-size: 0.75rem;
                white-space: nowrap;
            }

            .cp-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 20px;
            }

            @media (min-width: 720px) {
                .cp-actions { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            }

            .cp-action {
                display: flex;
                flex-direction: column;
                gap: 4px;
                min-height: 72px;
                padding: 14px 16px;
                border-radius: 12px;
                border: 1px solid var(--cp-border);
                background: var(--cp-surface);
                text-decoration: none;
                color: inherit;
                transition: border-color .15s ease, background .15s ease;
            }

            .cp-action:hover {
                border-color: var(--cp-accent);
                background: var(--cp-accent-soft);
            }

            .cp-action strong {
                font-size: 0.92rem;
                font-weight: 600;
            }

            .cp-action span {
                font-size: 0.75rem;
                color: var(--cp-muted);
                line-height: 1.35;
            }

            .cp-action.is-primary {
                background: var(--cp-accent);
                border-color: transparent;
                color: #fff7ed;
            }

            .dark .cp-action.is-primary {
                color: #18181b;
            }

            .cp-action.is-primary span {
                color: inherit;
                opacity: .85;
            }

            .cp-action.is-primary:hover {
                filter: brightness(1.05);
                background: var(--cp-accent);
            }

            .cp-kpis {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 20px;
            }

            @media (min-width: 900px) {
                .cp-kpis { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            }

            .cp-kpi {
                padding: 16px;
                border-radius: 12px;
                border: 1px solid var(--cp-border);
                background: var(--cp-surface);
                min-width: 0;
            }

            .cp-kpi .label {
                font-size: 0.7rem;
                font-weight: 600;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                color: var(--cp-muted);
            }

            .cp-kpi .value {
                margin-top: 8px;
                font-size: clamp(1.15rem, 2.5vw, 1.45rem);
                font-weight: 650;
                font-variant-numeric: tabular-nums;
                letter-spacing: -0.02em;
                line-height: 1.2;
                word-break: break-word;
            }

            .cp-kpi .hint {
                margin-top: 6px;
                font-size: 0.72rem;
                color: var(--cp-muted);
            }

            .cp-alert {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 20px;
                padding: 14px 16px;
                border-radius: 12px;
                border: 1px solid color-mix(in srgb, var(--cp-danger) 25%, var(--cp-border));
                background: var(--cp-danger-soft);
            }

            .cp-alert.is-ok {
                border-color: var(--cp-border);
                background: var(--cp-subtle);
            }

            .cp-alert strong {
                display: block;
                font-size: 0.95rem;
                font-weight: 600;
            }

            .cp-alert p {
                margin: 4px 0 0;
                font-size: 0.8rem;
                color: var(--cp-muted);
            }

            .cp-alert a {
                display: inline-flex;
                align-items: center;
                min-height: 36px;
                padding: 0 14px;
                border-radius: 8px;
                border: 1px solid var(--cp-border);
                background: var(--cp-surface);
                color: var(--cp-text);
                text-decoration: none;
                font-size: 0.8rem;
                font-weight: 600;
            }

            .cp-main {
                display: grid;
                gap: 12px;
            }

            @media (min-width: 960px) {
                .cp-main { grid-template-columns: 1.4fr 1fr; align-items: start; }
            }

            .cp-card {
                border: 1px solid var(--cp-border);
                border-radius: 12px;
                background: var(--cp-surface);
                padding: 16px;
                min-width: 0;
            }

            .cp-card-head {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 14px;
            }

            .cp-card-head h2 {
                margin: 0;
                font-size: 0.95rem;
                font-weight: 650;
            }

            .cp-card-head span {
                font-size: 0.72rem;
                color: var(--cp-muted);
            }

            .cp-chart {
                position: relative;
                height: 220px;
            }

            .cp-list {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .cp-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 10px 12px;
                border-radius: 10px;
                background: var(--cp-subtle);
                min-width: 0;
            }

            .cp-row .meta {
                min-width: 0;
            }

            .cp-row .meta strong {
                display: block;
                font-size: 0.85rem;
                font-weight: 600;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .cp-row .meta span {
                display: block;
                margin-top: 2px;
                font-size: 0.72rem;
                color: var(--cp-muted);
            }

            .cp-row .amount {
                font-size: 0.85rem;
                font-weight: 650;
                font-variant-numeric: tabular-nums;
                white-space: nowrap;
            }

            .cp-pill {
                display: inline-flex;
                align-items: center;
                padding: 3px 8px;
                border-radius: 999px;
                font-size: 0.72rem;
                font-weight: 650;
                font-variant-numeric: tabular-nums;
                background: var(--cp-danger-soft);
                color: var(--cp-danger);
            }

            .cp-empty {
                padding: 18px 12px;
                text-align: center;
                color: var(--cp-muted);
                font-size: 0.85rem;
            }
        </style>
    @endonce

    <div class="cp">
        <header class="cp-head">
            <div>
                <h1>Olá, {{ $userName }}</h1>
                <p>Vendas recebidas, estoque e atalhos do dia. Valores de faturamento consideram só vendas confirmadas e pagas.</p>
            </div>
            <div class="cp-updated">Atualizado {{ $updatedAt }}</div>
        </header>

        <nav class="cp-actions" aria-label="Ações rápidas">
            <a class="cp-action is-primary" href="{{ $urls['start_sale'] ?? '#' }}">
                <strong>Nova venda</strong>
                <span>Abrir o PDV</span>
            </a>
            <a class="cp-action" href="{{ $urls['products'] ?? '#' }}">
                <strong>Produtos</strong>
                <span>Cadastro e preços</span>
            </a>
            <a class="cp-action" href="{{ $urls['stock'] ?? '#' }}">
                <strong>Estoque</strong>
                <span>Movimentações</span>
            </a>
            <a class="cp-action" href="{{ $urls['report'] ?? '#' }}">
                <strong>Relatório</strong>
                <span>Vendas detalhadas</span>
            </a>
        </nav>

        <section class="cp-kpis" aria-label="Indicadores">
            <article class="cp-kpi">
                <div class="label">Recebido hoje</div>
                <div class="value">R$ {{ number_format($kpis['today_total'] ?? 0, 2, ',', '.') }}</div>
                <div class="hint">{{ $todayCount }} {{ $todayCount === 1 ? 'venda' : 'vendas' }}</div>
            </article>
            <article class="cp-kpi">
                <div class="label">Ticket médio</div>
                <div class="value">R$ {{ number_format($kpis['today_ticket'] ?? 0, 2, ',', '.') }}</div>
                <div class="hint">Hoje</div>
            </article>
            <article class="cp-kpi">
                <div class="label">Recebido no mês</div>
                <div class="value">R$ {{ number_format($kpis['month_total'] ?? 0, 2, ',', '.') }}</div>
                <div class="hint">Confirmadas e pagas</div>
            </article>
            <article class="cp-kpi">
                <div class="label">Produtos</div>
                <div class="value">{{ $kpis['products_total'] ?? 0 }}</div>
                <div class="hint">No catálogo</div>
            </article>
        </section>

        <aside class="cp-alert {{ $lowStockCount > 0 ? '' : 'is-ok' }}" aria-live="polite">
            <div>
                <strong>
                    @if ($lowStockCount > 0)
                        {{ $lowStockCount }} {{ $lowStockCount === 1 ? 'item' : 'itens' }} com estoque baixo
                    @else
                        Estoque sem alertas críticos
                    @endif
                </strong>
                <p>
                    @if ($lowStockCount > 0)
                        Priorize reposição ou ajuste de mínimo.
                    @else
                        Nenhum produto está no ou abaixo do mínimo agora.
                    @endif
                </p>
            </div>
            <a href="{{ $urls['stock'] ?? '#' }}">Ver estoque</a>
        </aside>

        <div class="cp-main">
            <section class="cp-card">
                <div class="cp-card-head">
                    <h2>Recebido nos últimos 7 dias</h2>
                    <span>R$ por dia</span>
                </div>
                <div class="cp-chart">
                    <canvas id="controlPanelTrend" aria-label="Gráfico de recebimentos"></canvas>
                </div>
            </section>

            <section class="cp-card">
                <div class="cp-card-head">
                    <h2>Estoque baixo</h2>
                    <span>Até 5 itens</span>
                </div>
                <div class="cp-list">
                    @forelse ($lowStock as $item)
                        <div class="cp-row">
                            <div class="meta">
                                <strong>{{ $item['name'] }}</strong>
                                <span>Mínimo {{ $item['min_stock'] }}</span>
                            </div>
                            <span class="cp-pill">{{ $item['stock'] }} un</span>
                        </div>
                    @empty
                        <div class="cp-empty">Nada crítico no momento.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="cp-card" style="margin-top: 12px;">
            <div class="cp-card-head">
                <h2>Últimas vendas recebidas</h2>
                <span>Mais recentes</span>
            </div>
            <div class="cp-list">
                @forelse ($recentSales as $sale)
                    <div class="cp-row">
                        <div class="meta">
                            <strong>#{{ $sale['id'] }} · {{ $sale['user'] }}</strong>
                            <span>{{ $sale['client'] }} · {{ $sale['created_at'] }}</span>
                        </div>
                        <div class="amount">R$ {{ number_format($sale['total'] ?? 0, 2, ',', '.') }}</div>
                    </div>
                @empty
                    <div class="cp-empty">Ainda não há vendas recebidas para mostrar.</div>
                @endforelse
            </div>
        </section>
    </div>

    @once
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            const controlPanelTrend = @json($trend);

            const chartColors = () => {
                const dark = document.documentElement.classList.contains('dark');
                return {
                    line: dark ? '#fbbf24' : '#b45309',
                    fill: dark ? 'rgba(251, 191, 36, 0.14)' : 'rgba(180, 83, 9, 0.12)',
                    tick: dark ? '#a1a1aa' : '#71717a',
                    grid: dark ? 'rgba(63, 63, 70, 0.8)' : 'rgba(228, 228, 231, 0.9)',
                };
            };

            const initControlPanelChart = () => {
                const ctx = document.getElementById('controlPanelTrend');
                if (!ctx || !window.Chart) return;

                if (window.controlPanelChart) {
                    window.controlPanelChart.destroy();
                }

                const colors = chartColors();
                window.controlPanelChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: controlPanelTrend.labels || [],
                        datasets: [{
                            label: 'Recebido (R$)',
                            data: controlPanelTrend.values || [],
                            borderColor: colors.line,
                            backgroundColor: colors.fill,
                            fill: true,
                            tension: 0.3,
                            pointRadius: 3,
                            pointHoverRadius: 4,
                            borderWidth: 2,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (item) => ` R$ ${Number(item.raw || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`,
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: colors.tick, maxRotation: 0 },
                                border: { display: false },
                            },
                            y: {
                                grid: { color: colors.grid },
                                ticks: { color: colors.tick },
                                border: { display: false },
                            },
                        },
                    },
                });
            };

            document.addEventListener('DOMContentLoaded', initControlPanelChart);
            document.addEventListener('livewire:navigated', initControlPanelChart);
        </script>
    @endonce
</x-filament-panels::page>
