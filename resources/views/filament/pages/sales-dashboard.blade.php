<x-filament-panels::page>
    @php
        $data = $dashboard ?? [];
        $kpis = $data['kpis'] ?? [];
        $charts = $data['charts'] ?? [];
        $products = $data['products'] ?? [];
        $stock = $data['stock'] ?? [];
        $hasCategoryData = collect($charts['categories']['values'] ?? [])->sum() > 0;
        $hasTopProducts = collect($charts['top_products']['values'] ?? [])->sum() > 0;
        $hasSalesTrend = collect($charts['sales']['values'] ?? [])->sum() > 0;
    @endphp

    @once
        <style>
            .sd {
                --sd-surface: #ffffff;
                --sd-subtle: #f4f4f5;
                --sd-border: #e4e4e7;
                --sd-text: #18181b;
                --sd-muted: #71717a;
                --sd-accent: #b45309;
                --sd-accent-soft: #fff7ed;
                --sd-danger: #b91c1c;
                --sd-danger-soft: #fef2f2;
                color: var(--sd-text);
                font-size: 14px;
                min-width: 0;
            }

            .dark .sd {
                --sd-surface: #18181b;
                --sd-subtle: #27272a;
                --sd-border: #3f3f46;
                --sd-text: #fafafa;
                --sd-muted: #a1a1aa;
                --sd-accent: #fbbf24;
                --sd-accent-soft: rgba(251, 191, 36, 0.12);
                --sd-danger: #f87171;
                --sd-danger-soft: rgba(248, 113, 113, 0.12);
            }

            .sd *, .sd *::before, .sd *::after { box-sizing: border-box; }

            .sd-head {
                display: flex;
                flex-wrap: wrap;
                align-items: flex-end;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 20px;
            }

            .sd-head h1 {
                margin: 0;
                font-size: clamp(1.35rem, 3vw, 1.75rem);
                font-weight: 650;
                letter-spacing: -0.02em;
                line-height: 1.2;
            }

            .sd-head p {
                margin: 6px 0 0;
                color: var(--sd-muted);
                font-size: 0.9rem;
                max-width: 38rem;
                line-height: 1.5;
            }

            .sd-updated {
                color: var(--sd-muted);
                font-size: 0.75rem;
                white-space: nowrap;
            }

            .sd-kpis {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 16px;
            }

            @media (min-width: 900px) {
                .sd-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            }

            .sd-kpi {
                padding: 16px;
                border: 1px solid var(--sd-border);
                border-radius: 12px;
                background: var(--sd-surface);
                min-width: 0;
            }

            .sd-kpi .label {
                font-size: 0.7rem;
                font-weight: 600;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                color: var(--sd-muted);
            }

            .sd-kpi .value {
                margin-top: 8px;
                font-size: clamp(1.15rem, 2.5vw, 1.45rem);
                font-weight: 650;
                font-variant-numeric: tabular-nums;
                letter-spacing: -0.02em;
                word-break: break-word;
            }

            .sd-kpi .hint {
                margin-top: 6px;
                font-size: 0.72rem;
                color: var(--sd-muted);
            }

            .sd-grid {
                display: grid;
                gap: 12px;
                margin-bottom: 12px;
            }

            @media (min-width: 960px) {
                .sd-grid.two { grid-template-columns: 1.35fr 1fr; }
                .sd-grid.three { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            }

            .sd-card {
                border: 1px solid var(--sd-border);
                border-radius: 12px;
                background: var(--sd-surface);
                padding: 16px;
                min-width: 0;
            }

            .sd-card-head {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 14px;
            }

            .sd-card-head h2 {
                margin: 0;
                font-size: 0.95rem;
                font-weight: 650;
            }

            .sd-card-head span {
                font-size: 0.72rem;
                color: var(--sd-muted);
            }

            .sd-chart {
                position: relative;
                height: 220px;
            }

            .sd-chart.is-tall { height: 260px; }

            .sd-empty {
                display: grid;
                place-items: center;
                min-height: 180px;
                padding: 20px;
                text-align: center;
                color: var(--sd-muted);
                font-size: 0.85rem;
                line-height: 1.5;
                border-radius: 10px;
                background: var(--sd-subtle);
            }

            .sd-list {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .sd-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 10px 12px;
                border-radius: 10px;
                background: var(--sd-subtle);
                min-width: 0;
            }

            .sd-row .meta {
                min-width: 0;
            }

            .sd-row .meta strong {
                display: block;
                font-size: 0.85rem;
                font-weight: 600;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .sd-row .meta span {
                display: block;
                margin-top: 2px;
                font-size: 0.72rem;
                color: var(--sd-muted);
            }

            .sd-row .amount {
                font-size: 0.85rem;
                font-weight: 650;
                font-variant-numeric: tabular-nums;
                white-space: nowrap;
            }

            .sd-pill {
                display: inline-flex;
                align-items: center;
                padding: 3px 8px;
                border-radius: 999px;
                font-size: 0.72rem;
                font-weight: 650;
                font-variant-numeric: tabular-nums;
                background: var(--sd-danger-soft);
                color: var(--sd-danger);
            }

            .sd-pill.is-ok {
                background: var(--sd-accent-soft);
                color: var(--sd-accent);
            }

            .sd-insight {
                display: grid;
                gap: 10px;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                margin-bottom: 12px;
            }

            @media (min-width: 900px) {
                .sd-insight { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            }

            .sd-insight article {
                padding: 14px;
                border: 1px solid var(--sd-border);
                border-radius: 12px;
                background: var(--sd-surface);
                min-width: 0;
            }

            .sd-insight .label {
                font-size: 0.7rem;
                font-weight: 600;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                color: var(--sd-muted);
            }

            .sd-insight .name {
                margin-top: 8px;
                font-size: 0.9rem;
                font-weight: 600;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .sd-insight .detail {
                margin-top: 4px;
                font-size: 0.75rem;
                color: var(--sd-muted);
                font-variant-numeric: tabular-nums;
            }
        </style>
    @endonce

    <div class="sd">
        <header class="sd-head">
            <div>
                <h1>Dashboard de vendas</h1>
                <p>Recebimentos confirmados, giro de produtos e estoque. Totais consideram só vendas pagas.</p>
            </div>
            <div class="sd-updated">Atualizado {{ $data['meta']['updated_at'] ?? '-' }}</div>
        </header>

        <section class="sd-kpis" aria-label="Indicadores">
            <article class="sd-kpi">
                <div class="label">Recebido (período)</div>
                <div class="value">R$ {{ number_format($kpis['total_sales'] ?? 0, 2, ',', '.') }}</div>
                <div class="hint">Vendas confirmadas e pagas</div>
            </article>
            <article class="sd-kpi">
                <div class="label">Ticket médio</div>
                <div class="value">R$ {{ number_format($kpis['ticket'] ?? 0, 2, ',', '.') }}</div>
                <div class="hint">Por venda recebida</div>
            </article>
            <article class="sd-kpi">
                <div class="label">Produtos</div>
                <div class="value">{{ $kpis['products_total'] ?? 0 }}</div>
                <div class="hint">No catálogo</div>
            </article>
        </section>

        <div class="sd-insight" aria-label="Destaques">
            <article>
                <div class="label">Mais vendido</div>
                <div class="name">{{ $products['most_sold']['name'] ?? '—' }}</div>
                <div class="detail">{{ $products['most_sold']['value'] ?? 0 }} un.</div>
            </article>
            <article>
                <div class="label">Maior faturamento</div>
                <div class="name">{{ $products['featured']['name'] ?? '—' }}</div>
                <div class="detail">R$ {{ number_format($products['featured']['value'] ?? 0, 2, ',', '.') }}</div>
            </article>
            <article>
                <div class="label">Preço mais alto</div>
                <div class="name">{{ $products['most_expensive']['name'] ?? '—' }}</div>
                <div class="detail">R$ {{ number_format($products['most_expensive']['value'] ?? 0, 2, ',', '.') }}</div>
            </article>
            <article>
                <div class="label">Preço mais baixo</div>
                <div class="name">{{ $products['cheapest']['name'] ?? '—' }}</div>
                <div class="detail">R$ {{ number_format($products['cheapest']['value'] ?? 0, 2, ',', '.') }}</div>
            </article>
        </div>

        <div class="sd-grid two">
            <section class="sd-card">
                <div class="sd-card-head">
                    <h2>Recebido nos últimos 12 dias</h2>
                    <span>R$ por dia</span>
                </div>
                @if ($hasSalesTrend)
                    <div class="sd-chart is-tall">
                        <canvas id="chartSalesTrend" aria-label="Gráfico de recebimentos"></canvas>
                    </div>
                @else
                    <div class="sd-empty">Ainda não há recebimentos neste período.<br>Confirme vendas no PDV para ver a curva.</div>
                @endif
            </section>

            <section class="sd-card">
                <div class="sd-card-head">
                    <h2>Por categoria</h2>
                    <span>Participação no recebido</span>
                </div>
                @if ($hasCategoryData)
                    <div class="sd-chart">
                        <canvas id="chartCategory" aria-label="Gráfico por categoria"></canvas>
                    </div>
                @else
                    <div class="sd-empty">Sem faturamento por categoria ainda.<br>Cadastre categorias nos produtos e registre vendas.</div>
                @endif
            </section>
        </div>

        <section class="sd-card" style="margin-bottom: 12px;">
            <div class="sd-card-head">
                <h2>Produtos mais vendidos</h2>
                <span>Top 6 por quantidade</span>
            </div>
            @if ($hasTopProducts)
                <div class="sd-chart">
                    <canvas id="chartTopProducts" aria-label="Gráfico de produtos mais vendidos"></canvas>
                </div>
            @else
                <div class="sd-empty">Nenhum ranking ainda — as primeiras vendas preencham esta lista.</div>
            @endif
        </section>

        <div class="sd-grid three">
            <section class="sd-card">
                <div class="sd-card-head">
                    <h2>Estoque</h2>
                    <span>Alto vs baixo</span>
                </div>
                <div class="sd-chart">
                    <canvas id="chartStock" aria-label="Gráfico de estoque"></canvas>
                </div>
            </section>

            <section class="sd-card">
                <div class="sd-card-head">
                    <h2>Estoque baixo</h2>
                    <span>Reposição</span>
                </div>
                <div class="sd-list">
                    @forelse ($stock['low'] ?? [] as $item)
                        <div class="sd-row">
                            <div class="meta">
                                <strong>{{ $item['name'] }}</strong>
                                <span>Mín. {{ $item['min_stock'] }}</span>
                            </div>
                            <span class="sd-pill">{{ $item['stock'] }} un</span>
                        </div>
                    @empty
                        <div class="sd-empty" style="min-height: 120px;">Nenhum item crítico.</div>
                    @endforelse
                </div>
            </section>

            <section class="sd-card">
                <div class="sd-card-head">
                    <h2>Maior saldo</h2>
                    <span>Acima do mínimo</span>
                </div>
                <div class="sd-list">
                    @forelse ($stock['high'] ?? [] as $item)
                        <div class="sd-row">
                            <div class="meta">
                                <strong>{{ $item['name'] }}</strong>
                                <span>Em estoque</span>
                            </div>
                            <span class="sd-pill is-ok">{{ $item['stock'] }} un</span>
                        </div>
                    @empty
                        <div class="sd-empty" style="min-height: 120px;">Sem dados.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    @once
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            const dashboardPayload = @json($data);

            const chartColors = () => {
                const dark = document.documentElement.classList.contains('dark');
                return {
                    line: dark ? '#fbbf24' : '#b45309',
                    fill: dark ? 'rgba(251, 191, 36, 0.14)' : 'rgba(180, 83, 9, 0.12)',
                    tick: dark ? '#a1a1aa' : '#71717a',
                    grid: dark ? 'rgba(63, 63, 70, 0.8)' : 'rgba(228, 228, 231, 0.9)',
                    bars: dark
                        ? ['#fbbf24', '#f59e0b', '#d97706', '#a16207', '#92400e', '#78350f']
                        : ['#b45309', '#c2410c', '#ea580c', '#f59e0b', '#d97706', '#92400e'],
                    stock: [dark ? '#4ade80' : '#15803d', dark ? '#f87171' : '#b91c1c'],
                };
            };

            const moneyLabel = (value) =>
                ` R$ ${Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;

            const initSalesDashboardCharts = () => {
                if (!window.Chart) return;
                (window.salesDashboardCharts || []).forEach((chart) => chart.destroy());
                window.salesDashboardCharts = [];

                const colors = chartColors();
                const charts = dashboardPayload.charts || {};

                const salesCtx = document.getElementById('chartSalesTrend');
                if (salesCtx) {
                    window.salesDashboardCharts.push(new Chart(salesCtx, {
                        type: 'line',
                        data: {
                            labels: charts.sales?.labels ?? [],
                            datasets: [{
                                data: charts.sales?.values ?? [],
                                borderColor: colors.line,
                                backgroundColor: colors.fill,
                                fill: true,
                                tension: 0.3,
                                pointRadius: 3,
                                borderWidth: 2,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: { callbacks: { label: (item) => moneyLabel(item.raw) } },
                            },
                            scales: {
                                x: { grid: { display: false }, ticks: { color: colors.tick, maxRotation: 0 }, border: { display: false } },
                                y: { grid: { color: colors.grid }, ticks: { color: colors.tick }, border: { display: false } },
                            },
                        },
                    }));
                }

                const categoryCtx = document.getElementById('chartCategory');
                if (categoryCtx) {
                    window.salesDashboardCharts.push(new Chart(categoryCtx, {
                        type: 'doughnut',
                        data: {
                            labels: charts.categories?.labels ?? [],
                            datasets: [{
                                data: charts.categories?.values ?? [],
                                backgroundColor: colors.bars,
                                borderWidth: 0,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: colors.tick, boxWidth: 10, font: { size: 11 } },
                                },
                                tooltip: { callbacks: { label: (item) => `${item.label}: ${moneyLabel(item.raw)}` } },
                            },
                        },
                    }));
                }

                const topProductsCtx = document.getElementById('chartTopProducts');
                if (topProductsCtx) {
                    window.salesDashboardCharts.push(new Chart(topProductsCtx, {
                        type: 'bar',
                        data: {
                            labels: charts.top_products?.labels ?? [],
                            datasets: [{
                                data: charts.top_products?.values ?? [],
                                backgroundColor: colors.line,
                                borderRadius: 6,
                                maxBarThickness: 28,
                            }],
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { grid: { color: colors.grid }, ticks: { color: colors.tick }, border: { display: false } },
                                y: { grid: { display: false }, ticks: { color: colors.tick }, border: { display: false } },
                            },
                        },
                    }));
                }

                const stockCtx = document.getElementById('chartStock');
                if (stockCtx) {
                    window.salesDashboardCharts.push(new Chart(stockCtx, {
                        type: 'doughnut',
                        data: {
                            labels: charts.stock?.labels ?? ['Estoque alto', 'Estoque baixo'],
                            datasets: [{
                                data: charts.stock?.values ?? [0, 0],
                                backgroundColor: colors.stock,
                                borderWidth: 0,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: colors.tick, boxWidth: 10, font: { size: 11 } },
                                },
                            },
                        },
                    }));
                }
            };

            document.addEventListener('DOMContentLoaded', initSalesDashboardCharts);
            document.addEventListener('livewire:navigated', initSalesDashboardCharts);
        </script>
    @endonce
</x-filament-panels::page>
