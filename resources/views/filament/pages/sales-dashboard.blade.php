<x-filament-panels::page>
    @php
        $data = $dashboard ?? [];
    @endphp

    @once
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@500;600&family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <style>
            .sales-dashboard {
                --bg: #f5f7fb;
                --card: #ffffff;
                --text: #0f172a;
                --muted: #6b7280;
                --border: rgba(15, 23, 42, 0.08);
                --accent: #2563eb;
                --accent-2: #10b981;
                --accent-3: #06b6d4;
                --warning: #f59e0b;
                --danger: #ef4444;
                font-family: "Sora", sans-serif;
                color: var(--text);
            }

            .sales-dashboard * {
                box-sizing: border-box;
            }

            .sales-shell {
                background:
                    radial-gradient(900px 400px at 10% -10%, rgba(37, 99, 235, 0.12), transparent 55%),
                    radial-gradient(800px 400px at 90% 0%, rgba(16, 185, 129, 0.12), transparent 50%),
                    radial-gradient(600px 400px at 70% 90%, rgba(6, 182, 212, 0.12), transparent 50%),
                    var(--bg);
                border: 1px solid var(--border);
                border-radius: 24px;
                padding: 24px;
            }

            .dash-header {
                display: flex;
                flex-wrap: wrap;
                align-items: flex-end;
                justify-content: space-between;
                gap: 16px;
                margin-bottom: 24px;
            }

            .dash-title {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .dash-title .eyebrow {
                font-size: 12px;
                letter-spacing: 0.18em;
                text-transform: uppercase;
                color: var(--accent);
                font-weight: 600;
            }

            .dash-title h1 {
                font-family: "Newsreader", serif;
                font-weight: 600;
                font-size: clamp(26px, 3vw, 34px);
                margin: 0;
            }

            .dash-title p {
                margin: 0;
                color: var(--muted);
                max-width: 520px;
                font-size: 14px;
                line-height: 1.6;
            }

            .dash-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .pill {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 8px 14px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.8);
                border: 1px solid var(--border);
                font-size: 12px;
                color: var(--muted);
                font-weight: 500;
            }

            .pill span {
                width: 8px;
                height: 8px;
                border-radius: 999px;
                background: var(--accent-2);
            }

            .kpi-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 16px;
                margin-bottom: 24px;
            }

            .kpi-card {
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 18px;
                padding: 18px;
                box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
                position: relative;
                overflow: hidden;
                animation: rise 0.6s ease both;
            }

            .kpi-card::after {
                content: "";
                position: absolute;
                inset: auto -30% -60% auto;
                width: 120px;
                height: 120px;
                border-radius: 50%;
                background: rgba(37, 99, 235, 0.08);
            }

            .kpi-card .icon {
                width: 42px;
                height: 42px;
                display: grid;
                place-items: center;
                border-radius: 14px;
                background: rgba(37, 99, 235, 0.1);
                color: var(--accent);
                font-size: 18px;
            }

            .kpi-card .label {
                margin-top: 12px;
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: 0.12em;
                color: var(--muted);
            }

            .kpi-card .value {
                margin-top: 6px;
                font-size: 22px;
                font-weight: 600;
                color: var(--text);
            }

            .grid-two {
                display: grid;
                gap: 16px;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                margin-bottom: 24px;
            }

            .card {
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 20px;
                padding: 18px;
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
                animation: rise 0.6s ease both;
            }

            .card-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 12px;
            }

            .card-header h3 {
                font-size: 15px;
                margin: 0;
                font-weight: 600;
            }

            .card-header span {
                font-size: 12px;
                color: var(--muted);
            }

            .chart-wrap {
                position: relative;
                height: 280px;
            }

            .chart-wrap.tall {
                height: 320px;
            }

            .product-grid {
                display: grid;
                gap: 16px;
                grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
                margin-bottom: 24px;
            }

            .product-card {
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 18px;
                padding: 16px;
                box-shadow: 0 10px 20px rgba(15, 23, 42, 0.06);
                position: relative;
            }

            .product-card .badge {
                position: absolute;
                top: 14px;
                right: 14px;
                padding: 4px 10px;
                border-radius: 999px;
                background: rgba(16, 185, 129, 0.12);
                color: var(--accent-2);
                font-size: 11px;
                font-weight: 600;
            }

            .product-card h4 {
                margin: 10px 0 4px;
                font-size: 15px;
                font-weight: 600;
            }

            .product-card p {
                margin: 0;
                color: var(--muted);
                font-size: 13px;
            }

            .stacked-grid {
                display: grid;
                gap: 16px;
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                margin-bottom: 24px;
            }

            .list {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .list-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: rgba(15, 23, 42, 0.04);
                border-radius: 12px;
                padding: 10px 12px;
                font-size: 13px;
            }

            .list-item strong {
                font-weight: 600;
            }

            .list-item.low {
                border-left: 4px solid var(--danger);
            }

            .list-item.high {
                border-left: 4px solid var(--accent-2);
            }

            .favorites {
                display: grid;
                gap: 12px;
            }

            .favorite-row {
                display: flex;
                flex-direction: column;
                gap: 6px;
                padding: 10px 12px;
                border-radius: 14px;
                background: rgba(37, 99, 235, 0.06);
            }

            .favorite-row span {
                font-size: 13px;
                font-weight: 600;
            }

            .favorite-bar {
                height: 6px;
                border-radius: 999px;
                background: rgba(37, 99, 235, 0.2);
                overflow: hidden;
            }

            .favorite-bar div {
                height: 100%;
                background: var(--accent);
                border-radius: 999px;
                width: 0;
                transition: width 0.8s ease;
            }

            @keyframes rise {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            @media (max-width: 768px) {
                .sales-shell {
                    padding: 16px;
                }
            }
        </style>
    @endonce

    <div class="sales-dashboard">
        <div class="sales-shell">
            <div class="dash-header">
                <div class="dash-title">
                    <span class="eyebrow">Painel Comercial</span>
                    <h1>Dashboard de Vendas</h1>
                    <p>Visão consolidada de vendas, produtos e estoque com foco no que realmente importa.</p>
                </div>
                <div class="dash-meta">
                    <span class="pill"><span></span>Atualizado em {{ $data['meta']['updated_at'] ?? '-' }}</span>
                </div>
            </div>

            <div class="kpi-grid">
                <div class="kpi-card" style="animation-delay: 0ms;">
                    <div class="icon"><i class="fa-solid fa-sack-dollar"></i></div>
                    <div class="label">Total de vendas</div>
                    <div class="value">R$ {{ number_format($data['kpis']['total_sales'] ?? 0, 2, ',', '.') }}</div>
                </div>
                <div class="kpi-card" style="animation-delay: 120ms;">
                    <div class="icon" style="background: rgba(16, 185, 129, 0.12); color: var(--accent-2);">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div class="label">Ticket médio</div>
                    <div class="value">R$ {{ number_format($data['kpis']['ticket'] ?? 0, 2, ',', '.') }}</div>
                </div>
                <div class="kpi-card" style="animation-delay: 240ms;">
                    <div class="icon" style="background: rgba(6, 182, 212, 0.12); color: var(--accent-3);">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div class="label">Produtos cadastrados</div>
                    <div class="value">{{ $data['kpis']['products_total'] ?? 0 }}</div>
                </div>
            </div>

            <div class="grid-two">
                <div class="card" style="animation-delay: 60ms;">
                    <div class="card-header">
                        <h3>Vendas por período</h3>
                        <span>Últimos 12 dias</span>
                    </div>
                    <div class="chart-wrap tall">
                        <canvas id="chartSalesTrend"></canvas>
                    </div>
                </div>
                <div class="card" style="animation-delay: 140ms;">
                    <div class="card-header">
                        <h3>Distribuição por categoria</h3>
                        <span>Participação no faturamento</span>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="chartCategory"></canvas>
                    </div>
                </div>
            </div>

            <div class="card" style="animation-delay: 180ms; margin-bottom: 24px;">
                <div class="card-header">
                    <h3>Produtos mais vendidos</h3>
                    <span>Top 6 por quantidade</span>
                </div>
                <div class="chart-wrap">
                    <canvas id="chartTopProducts"></canvas>
                </div>
            </div>

            <div class="product-grid">
                <div class="product-card">
                    <i class="fa-solid fa-medal" style="color: var(--accent);"></i>
                    <h4>Produto mais vendido</h4>
                    <p>{{ $data['products']['most_sold']['name'] ?? '—' }}</p>
                    <p><strong>{{ $data['products']['most_sold']['value'] ?? 0 }}</strong> unidades</p>
                </div>
                <div class="product-card">
                    <i class="fa-solid fa-gem" style="color: var(--accent-3);"></i>
                    <h4>Produto mais caro</h4>
                    <p>{{ $data['products']['most_expensive']['name'] ?? '—' }}</p>
                    <p><strong>R$ {{ number_format($data['products']['most_expensive']['value'] ?? 0, 2, ',', '.') }}</strong></p>
                </div>
                <div class="product-card">
                    <i class="fa-solid fa-tags" style="color: var(--warning);"></i>
                    <h4>Produto mais barato</h4>
                    <p>{{ $data['products']['cheapest']['name'] ?? '—' }}</p>
                    <p><strong>R$ {{ number_format($data['products']['cheapest']['value'] ?? 0, 2, ',', '.') }}</strong></p>
                </div>
                <div class="product-card">
                    <span class="badge">Em destaque</span>
                    <i class="fa-solid fa-star" style="color: var(--accent-2);"></i>
                    <h4>Produto em destaque</h4>
                    <p>{{ $data['products']['featured']['name'] ?? '—' }}</p>
                    <p><strong>R$ {{ number_format($data['products']['featured']['value'] ?? 0, 2, ',', '.') }}</strong> em vendas</p>
                </div>
            </div>

            <div class="stacked-grid">
                <div class="card">
                    <div class="card-header">
                        <h3>Estoque</h3>
                        <span>Alto vs baixo</span>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="chartStock"></canvas>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3>Estoque baixo</h3>
                        <span>Precisa de reposição</span>
                    </div>
                    <div class="list">
                        @forelse ($data['stock']['low'] ?? [] as $item)
                            <div class="list-item low">
                                <span>{{ $item['name'] }}</span>
                                <strong>{{ $item['stock'] }} un</strong>
                            </div>
                        @empty
                            <div class="list-item low">Nenhum item crítico.</div>
                        @endforelse
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3>Estoque alto</h3>
                        <span>Produtos com folga</span>
                    </div>
                    <div class="list">
                        @forelse ($data['stock']['high'] ?? [] as $item)
                            <div class="list-item high">
                                <span>{{ $item['name'] }}</span>
                                <strong>{{ $item['stock'] }} un</strong>
                            </div>
                        @empty
                            <div class="list-item high">Sem dados disponíveis.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Produtos favoritados</h3>
                    <span>Top 5</span>
                </div>
                <div class="favorites">
                    @forelse ($data['favorites']['top'] ?? [] as $index => $fav)
                        <div class="favorite-row">
                            <span>{{ $fav['name'] }}</span>
                            <div class="favorite-bar">
                                <div style="width: {{ min(100, ($fav['count'] ?? 0) * 10) }}%"></div>
                            </div>
                            <div class="text-xs text-gray-500">{{ $fav['count'] }} favoritos</div>
                        </div>
                    @empty
                        <div class="favorite-row">
                            <span>Sem favoritos registrados</span>
                            <div class="favorite-bar">
                                <div style="width: 12%"></div>
                            </div>
                            <div class="text-xs text-gray-500">Crie favoritos para gerar o ranking</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @once
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            const dashboardPayload = @json($data);

            const chartTheme = {
                text: '#0f172a',
                grid: 'rgba(15, 23, 42, 0.08)',
                blue: '#2563eb',
                green: '#10b981',
                teal: '#06b6d4',
                amber: '#f59e0b',
            };

            const initSalesDashboardCharts = () => {
                const existingCharts = window.salesDashboardCharts || [];
                existingCharts.forEach(chart => chart.destroy());
                window.salesDashboardCharts = [];

                const salesCtx = document.getElementById('chartSalesTrend');
                const categoryCtx = document.getElementById('chartCategory');
                const topProductsCtx = document.getElementById('chartTopProducts');
                const stockCtx = document.getElementById('chartStock');

                if (!salesCtx || !categoryCtx || !topProductsCtx || !stockCtx) {
                    return;
                }

                // Vendas por período
                window.salesDashboardCharts.push(new Chart(salesCtx, {
                    type: 'line',
                    data: {
                        labels: dashboardPayload.charts?.sales?.labels ?? [],
                        datasets: [{
                            label: 'Vendas (R$)',
                            data: dashboardPayload.charts?.sales?.values ?? [],
                            borderColor: chartTheme.blue,
                            backgroundColor: 'rgba(37, 99, 235, 0.15)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
                            pointBackgroundColor: chartTheme.blue,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => ` R$ ${Number(ctx.raw || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`,
                                },
                            },
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: chartTheme.text } },
                            y: { grid: { color: chartTheme.grid }, ticks: { color: chartTheme.text } },
                        },
                    },
                }));

                // Distribuição por categoria
                window.salesDashboardCharts.push(new Chart(categoryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: dashboardPayload.charts?.categories?.labels ?? [],
                        datasets: [{
                            data: dashboardPayload.charts?.categories?.values ?? [],
                            backgroundColor: [chartTheme.blue, chartTheme.green, chartTheme.teal, chartTheme.amber, '#94a3b8'],
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { color: chartTheme.text, boxWidth: 10, boxHeight: 10 },
                            },
                        },
                        cutout: '60%',
                    },
                }));

                // Produtos mais vendidos
                window.salesDashboardCharts.push(new Chart(topProductsCtx, {
                    type: 'bar',
                    data: {
                        labels: dashboardPayload.charts?.top_products?.labels ?? [],
                        datasets: [{
                            label: 'Unidades',
                            data: dashboardPayload.charts?.top_products?.values ?? [],
                            backgroundColor: 'rgba(16, 185, 129, 0.7)',
                            borderRadius: 8,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { grid: { color: chartTheme.grid }, ticks: { color: chartTheme.text } },
                            y: { grid: { display: false }, ticks: { color: chartTheme.text } },
                        },
                    },
                }));

                // Estoque alto vs baixo
                window.salesDashboardCharts.push(new Chart(stockCtx, {
                    type: 'bar',
                    data: {
                        labels: dashboardPayload.charts?.stock?.labels ?? [],
                        datasets: [{
                            data: dashboardPayload.charts?.stock?.values ?? [],
                            backgroundColor: ['rgba(16, 185, 129, 0.7)', 'rgba(239, 68, 68, 0.6)'],
                            borderRadius: 10,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: chartTheme.text } },
                            y: { grid: { color: chartTheme.grid }, ticks: { color: chartTheme.text } },
                        },
                    },
                }));
            };

            document.addEventListener('DOMContentLoaded', initSalesDashboardCharts);
            document.addEventListener('livewire:navigated', initSalesDashboardCharts);
        </script>
    @endonce
</x-filament-panels::page>
