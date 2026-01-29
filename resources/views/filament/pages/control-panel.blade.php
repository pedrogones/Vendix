<x-filament-panels::page>
    @php
        $kpis = $data['kpis'] ?? [];
        $trend = $data['trend'] ?? ['labels' => [], 'values' => []];
        $lowStock = $data['low_stock'] ?? [];
        $recentSales = $data['recent_sales'] ?? [];
        $updatedAt = $data['meta']['updated_at'] ?? '-';
        $userName = auth()->user()?->name ?? 'Usuário';
        $urls = $urls ?? [];
    @endphp

    @once
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@500;600&family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <style>
            .control-panel {
                --bg: #f5f7fb;
                --card: #ffffff;
                --text: #0f172a;
                --muted: #64748b;
                --border: rgba(15, 23, 42, 0.08);
                --accent: #2563eb;
                --accent-2: #16a34a;
                --accent-3: #0ea5e9;
                --warning: #f59e0b;
                --danger: #ef4444;
                --shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
                font-family: "Sora", sans-serif;
                color: var(--text);
            }

            .dark .control-panel {
                --bg: #0b1120;
                --card: rgba(15, 23, 42, 0.92);
                --text: #e2e8f0;
                --muted: #94a3b8;
                --border: rgba(148, 163, 184, 0.18);
                --shadow: none;
            }

            .control-panel * {
                box-sizing: border-box;
            }

            .cp-shell {
                background:
                    radial-gradient(900px 420px at 10% -10%, rgba(37, 99, 235, 0.12), transparent 55%),
                    radial-gradient(800px 420px at 100% 0%, rgba(14, 165, 233, 0.12), transparent 55%),
                    radial-gradient(700px 420px at 50% 100%, rgba(22, 163, 74, 0.12), transparent 55%),
                    var(--bg);
                border: 1px solid var(--border);
                border-radius: 24px;
                padding: 24px;
            }

            .cp-hero {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
                margin-bottom: 24px;
            }

            .cp-hero-main {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .cp-hero-main .eyebrow {
                font-size: 11px;
                letter-spacing: 0.18em;
                text-transform: uppercase;
                color: var(--accent);
                font-weight: 600;
            }

            .cp-hero-main h1 {
                font-family: "Newsreader", serif;
                font-size: clamp(26px, 3vw, 34px);
                margin: 0;
                font-weight: 600;
            }

            .cp-hero-main p {
                margin: 0;
                color: var(--muted);
                max-width: 520px;
                font-size: 14px;
                line-height: 1.6;
            }

            .cp-hero-meta {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 12px;
                min-width: 260px;
            }

            .meta-card {
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 16px;
                padding: 14px 16px;
                box-shadow: var(--shadow);
            }

            .meta-card.accent {
                background: rgba(37, 99, 235, 0.08);
                border-color: rgba(37, 99, 235, 0.22);
            }

            .meta-label {
                font-size: 11px;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: var(--muted);
                font-weight: 600;
            }

            .meta-value {
                margin-top: 6px;
                font-size: 18px;
                font-weight: 600;
                color: var(--text);
            }

            .kpi-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
                gap: 16px;
                margin-bottom: 24px;
            }

            .kpi-card {
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 18px;
                padding: 16px;
                box-shadow: var(--shadow);
                position: relative;
                overflow: hidden;
                animation: rise 0.6s ease both;
            }

            .kpi-card::after {
                content: "";
                position: absolute;
                inset: auto -30% -60% auto;
                width: 110px;
                height: 110px;
                border-radius: 50%;
                background: rgba(37, 99, 235, 0.08);
            }

            .kpi-card .icon {
                width: 40px;
                height: 40px;
                display: grid;
                place-items: center;
                border-radius: 12px;
                background: rgba(37, 99, 235, 0.12);
                color: var(--accent);
                font-size: 16px;
            }

            .kpi-card .label {
                margin-top: 10px;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.14em;
                color: var(--muted);
            }

            .kpi-card .value {
                margin-top: 6px;
                font-size: 20px;
                font-weight: 600;
                color: var(--text);
            }

            .action-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
                gap: 14px;
                margin-bottom: 24px;
            }

            .action-card {
                display: flex;
                align-items: center;
                gap: 14px;
                padding: 14px 16px;
                border-radius: 16px;
                background: var(--card);
                border: 1px solid var(--border);
                text-decoration: none;
                color: inherit;
                box-shadow: var(--shadow);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .action-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 18px 30px rgba(15, 23, 42, 0.14);
            }

            .action-card .icon {
                width: 40px;
                height: 40px;
                border-radius: 12px;
                display: grid;
                place-items: center;
                background: rgba(14, 165, 233, 0.12);
                color: var(--accent-3);
            }

            .action-card strong {
                display: block;
                font-size: 14px;
                font-weight: 600;
            }

            .action-card span {
                display: block;
                font-size: 12px;
                color: var(--muted);
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
                box-shadow: var(--shadow);
                animation: rise 0.6s ease both;
            }

            .card-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 12px;
            }

            .card-header h3 {
                margin: 0;
                font-size: 15px;
                font-weight: 600;
            }

            .card-header span {
                font-size: 12px;
                color: var(--muted);
            }

            .chart-wrap {
                position: relative;
                height: 260px;
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
                gap: 12px;
                padding: 10px 12px;
                border-radius: 12px;
                background: rgba(15, 23, 42, 0.04);
                font-size: 13px;
            }

            .dark .list-item {
                background: rgba(148, 163, 184, 0.12);
            }

            .list-item .meta {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }

            .list-item strong {
                font-weight: 600;
            }

            .badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 4px 10px;
                border-radius: 999px;
                background: rgba(239, 68, 68, 0.12);
                color: var(--danger);
                font-size: 11px;
                font-weight: 600;
            }

            .trend-summary {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                gap: 10px;
                margin-top: 16px;
            }

            .trend-pill {
                border-radius: 14px;
                padding: 10px 12px;
                border: 1px solid var(--border);
                background: rgba(37, 99, 235, 0.08);
                font-size: 12px;
            }

            .grid-three {
                display: grid;
                gap: 16px;
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            }

            @keyframes rise {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            @media (max-width: 768px) {
                .cp-shell {
                    padding: 16px;
                }

                .cp-hero-meta {
                    width: 100%;
                }
            }
        </style>
    @endonce

    <div class="control-panel">
        <div class="cp-shell">
            <div class="cp-hero">
                <div class="cp-hero-main">
                    <span class="eyebrow">Painel de Controle</span>
                    <h1>Olá, {{ $userName }}</h1>
                    <p>Resumo rápido do dia para acompanhar vendas, estoque e ações essenciais.</p>
                </div>
                <div class="cp-hero-meta">
                    <div class="meta-card">
                        <div class="meta-label">Atualizado</div>
                        <div class="meta-value">{{ $updatedAt }}</div>
                    </div>
                    <div class="meta-card accent">
                        <div class="meta-label">Estoque crítico</div>
                        <div class="meta-value">{{ $kpis['low_stock'] ?? 0 }} itens</div>
                    </div>
                </div>
            </div>

            <div class="kpi-grid">
                <div class="kpi-card" style="animation-delay: 0ms;">
                    <div class="icon"><i class="fa-solid fa-sack-dollar"></i></div>
                    <div class="label">Vendas hoje</div>
                    <div class="value">R$ {{ number_format($kpis['today_total'] ?? 0, 2, ',', '.') }}</div>
                </div>
                <div class="kpi-card" style="animation-delay: 80ms;">
                    <div class="icon" style="background: rgba(22, 163, 74, 0.12); color: var(--accent-2);">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div class="label">Ticket médio</div>
                    <div class="value">R$ {{ number_format($kpis['today_ticket'] ?? 0, 2, ',', '.') }}</div>
                </div>
                <div class="kpi-card" style="animation-delay: 160ms;">
                    <div class="icon" style="background: rgba(14, 165, 233, 0.12); color: var(--accent-3);">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div class="label">Faturamento do mês</div>
                    <div class="value">R$ {{ number_format($kpis['month_total'] ?? 0, 2, ',', '.') }}</div>
                </div>
                <div class="kpi-card" style="animation-delay: 240ms;">
                    <div class="icon" style="background: rgba(245, 158, 11, 0.12); color: var(--warning);">
                        <i class="fa-solid fa-box"></i>
                    </div>
                    <div class="label">Produtos cadastrados</div>
                    <div class="value">{{ $kpis['products_total'] ?? 0 }}</div>
                </div>
                <div class="kpi-card" style="animation-delay: 320ms;">
                    <div class="icon" style="background: rgba(239, 68, 68, 0.12); color: var(--danger);">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="label">Itens com estoque baixo</div>
                    <div class="value">{{ $kpis['low_stock'] ?? 0 }}</div>
                </div>
            </div>

            <div class="action-grid">
                <a class="action-card" href="{{ $urls['start_sale'] ?? '#' }}" wire:navigate>
                    <div class="icon"><i class="fa-solid fa-cash-register"></i></div>
                    <div>
                        <strong>Nova venda</strong>
                        <span>Abra um atendimento rápido</span>
                    </div>
                </a>
                <a class="action-card" href="{{ $urls['products'] ?? '#' }}" wire:navigate>
                    <div class="icon" style="background: rgba(22, 163, 74, 0.12); color: var(--accent-2);">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div>
                        <strong>Produtos</strong>
                        <span>Cadastre ou atualize itens</span>
                    </div>
                </a>
                <a class="action-card" href="{{ $urls['stock'] ?? '#' }}" wire:navigate>
                    <div class="icon" style="background: rgba(245, 158, 11, 0.12); color: var(--warning);">
                        <i class="fa-solid fa-warehouse"></i>
                    </div>
                    <div>
                        <strong>Estoque</strong>
                        <span>Registre entradas e saídas</span>
                    </div>
                </a>
                <a class="action-card" href="{{ $urls['report'] ?? '#' }}" wire:navigate>
                    <div class="icon" style="background: rgba(37, 99, 235, 0.12); color: var(--accent);">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>
                    <div>
                        <strong>Relatórios</strong>
                        <span>Analise resultados detalhados</span>
                    </div>
                </a>
            </div>

            <div class="grid-two">
                <div class="card" style="animation-delay: 120ms;">
                    <div class="card-header">
                        <h3>Vendas nos últimos 7 dias</h3>
                        <span>Tendência diária</span>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="controlPanelTrend"></canvas>
                    </div>
                    <div class="trend-summary">
                        <div class="trend-pill">
                            <strong>Hoje:</strong>
                            R$ {{ number_format($kpis['today_total'] ?? 0, 2, ',', '.') }}
                        </div>
                        <div class="trend-pill">
                            <strong>Mês:</strong>
                            R$ {{ number_format($kpis['month_total'] ?? 0, 2, ',', '.') }}
                        </div>
                    </div>
                </div>
                <div class="card" style="animation-delay: 200ms;">
                    <div class="card-header">
                        <h3>Alertas de estoque</h3>
                        <span>Itens abaixo do mínimo</span>
                    </div>
                    <div class="list">
                        @forelse ($lowStock as $item)
                            <div class="list-item">
                                <div class="meta">
                                    <strong>{{ $item['name'] }}</strong>
                                    <span class="text-xs text-gray-500">Mínimo: {{ $item['min_stock'] }}</span>
                                </div>
                                <span class="badge">{{ $item['stock'] }} un</span>
                            </div>
                        @empty
                            <div class="list-item">
                                <span>Nenhum item crítico no momento.</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="grid-three">
                <div class="card" style="animation-delay: 160ms;">
                    <div class="card-header">
                        <h3>Últimas vendas</h3>
                        <span>Atendimentos confirmados</span>
                    </div>
                    <div class="list">
                        @forelse ($recentSales as $sale)
                            <div class="list-item">
                                <div class="meta">
                                    <strong>#{{ $sale['id'] }}</strong>
                                    <span class="text-xs text-gray-500">{{ $sale['client'] }} - {{ $sale['created_at'] }}</span>
                                </div>
                                <strong>R$ {{ number_format($sale['total'] ?? 0, 2, ',', '.') }}</strong>
                            </div>
                        @empty
                            <div class="list-item">
                                <span>Sem vendas recentes.</span>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="card" style="animation-delay: 240ms;">
                    <div class="card-header">
                        <h3>Equipe em foco</h3>
                        <span>Vendas do dia</span>
                    </div>
                    <div class="list">
                        <div class="list-item">
                            <div class="meta">
                                <strong>Operador ativo</strong>
                                <span class="text-xs text-gray-500">{{ $userName }}</span>
                            </div>
                            <strong>{{ $kpis['today_total'] ?? 0 ? 'Online' : 'Aguardando vendas' }}</strong>
                        </div>
                        <div class="list-item">
                            <div class="meta">
                                <strong>Status de caixa</strong>
                                <span class="text-xs text-gray-500">Vendas confirmadas</span>
                            </div>
                            <strong>{{ $kpis['today_total'] ?? 0 ? 'Ativo' : 'Sem movimento' }}</strong>
                        </div>
                        <div class="list-item">
                            <div class="meta">
                                <strong>Produtos ativos</strong>
                                <span class="text-xs text-gray-500">Catálogo disponível</span>
                            </div>
                            <strong>{{ $kpis['products_total'] ?? 0 }}</strong>
                        </div>
                    </div>
                </div>
                <div class="card" style="animation-delay: 320ms;">
                    <div class="card-header">
                        <h3>Checklist do dia</h3>
                        <span>Ações rápidas</span>
                    </div>
                    <div class="list">
                        <div class="list-item">
                            <div class="meta">
                                <strong>Revisar estoque crítico</strong>
                                <span class="text-xs text-gray-500">{{ $kpis['low_stock'] ?? 0 }} itens</span>
                            </div>
                            <strong>Prioridade alta</strong>
                        </div>
                        <div class="list-item">
                            <div class="meta">
                                <strong>Atualizar preços</strong>
                                <span class="text-xs text-gray-500">Produtos em promoção</span>
                            </div>
                            <strong>Planejado</strong>
                        </div>
                        <div class="list-item">
                            <div class="meta">
                                <strong>Gerar relatório</strong>
                                <span class="text-xs text-gray-500">Resumo do dia</span>
                            </div>
                            <strong>Recomendado</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @once
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            const controlPanelTrend = @json($trend);

            const initControlPanelChart = () => {
                const ctx = document.getElementById('controlPanelTrend');
                if (!ctx || !window.Chart) {
                    return;
                }

                if (window.controlPanelChart) {
                    window.controlPanelChart.destroy();
                }

                window.controlPanelChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: controlPanelTrend.labels || [],
                        datasets: [{
                            label: 'Vendas (R$)',
                            data: controlPanelTrend.values || [],
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.18)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
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
                            x: { grid: { display: false }, ticks: { color: '#64748b' } },
                            y: { grid: { color: 'rgba(15, 23, 42, 0.08)' }, ticks: { color: '#64748b' } },
                        },
                    },
                });
            };

            document.addEventListener('DOMContentLoaded', initControlPanelChart);
            document.addEventListener('livewire:navigated', initControlPanelChart);
        </script>
    @endonce
</x-filament-panels::page>
