<!doctype html>
<html lang="pt-BR" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ config('app.name', 'ERP Estoque e Vendas') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>
<body class="min-h-full bg-white text-slate-900 antialiased">
<div class="relative isolate overflow-hidden">
    <div class="pointer-events-none absolute inset-0 -z-10">
        <div class="absolute -top-24 left-1/2 h-72 w-[44rem] -translate-x-1/2 rounded-full bg-gradient-to-r from-indigo-200 via-sky-200 to-emerald-200 blur-3xl opacity-70"></div>
        <div class="absolute -bottom-24 right-[-6rem] h-72 w-[40rem] rounded-full bg-gradient-to-r from-slate-100 via-indigo-100 to-sky-100 blur-3xl opacity-80"></div>
    </div>

    <header class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <nav class="flex items-center justify-between py-6">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-sm">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 6h10M7 12h10M7 18h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="leading-tight">
                    <p class="text-sm font-semibold text-slate-900">{{ config('app.name', 'ERP Estoque e Vendas') }}</p>
                    <p class="text-xs text-slate-500">Vendas, estoque e relatórios em um só lugar</p>
                </div>
            </div>

            <div class="hidden items-center gap-8 lg:flex">
                <a href="#beneficios" class="text-sm font-medium text-slate-700 hover:text-slate-900">Benefícios</a>
                <a href="#recursos" class="text-sm font-medium text-slate-700 hover:text-slate-900">Recursos</a>
                <a href="#visao" class="text-sm font-medium text-slate-700 hover:text-slate-900">Visão do produto</a>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ url('/admin/login') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                    Entrar no sistema
                </a>
            </div>
        </nav>
    </header>

    <main>
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 pb-16 pt-10 lg:grid-cols-2 lg:pb-24 lg:pt-16">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        ERP leve para pequenas e médias empresas
                    </div>

                    <h1 class="mt-6 text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
                        Onde suas vendas e seu estoque se encontram
                    </h1>

                    <p class="mt-5 text-lg leading-relaxed text-slate-600">
                        Controle produtos, acompanhe entradas e saídas, registre vendas e visualize relatórios com rapidez.
                        Tudo com uma interface clara e feita para o dia a dia da sua equipe.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <a href="{{ url('/admin/login') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                            Entrar no sistema
                        </a>

                        <a href="#beneficios" class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50">
                            Ver como funciona
                        </a>
                    </div>

                    <div class="mt-10 grid grid-cols-2 gap-4 sm:max-w-md">
                        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                            <p class="text-sm font-semibold text-slate-900">Estoque em tempo real</p>
                            <p class="mt-1 text-sm text-slate-600">Menos erros e mais controle</p>
                        </div>
                        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                            <p class="text-sm font-semibold text-slate-900">Vendas organizadas</p>
                            <p class="mt-1 text-sm text-slate-600">Atendimento ágil e confiável</p>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <div class="absolute -inset-6 -z-10 rounded-[2.5rem] bg-gradient-to-br from-slate-50 via-white to-indigo-50"></div>

                    <div class="relative rounded-[2rem] bg-white p-4 shadow-xl ring-1 ring-slate-200">
                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="h-3 w-3 rounded-full bg-rose-400"></div>
                                <div class="h-3 w-3 rounded-full bg-amber-300"></div>
                                <div class="h-3 w-3 rounded-full bg-emerald-400"></div>
                            </div>
                            <div class="text-xs font-semibold text-slate-600">Painel do sistema</div>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-12">
                            <div class="lg:col-span-5">
                                <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                                    <p class="text-xs font-semibold text-slate-500">Resumo</p>
                                    <div class="mt-3 space-y-3">
                                        <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                                            <span class="text-sm font-semibold text-slate-900">Itens em estoque</span>
                                            <span class="text-sm font-bold text-slate-900">1.248</span>
                                        </div>
                                        <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                                            <span class="text-sm font-semibold text-slate-900">Vendas do dia</span>
                                            <span class="text-sm font-bold text-emerald-600">R$ 4.920</span>
                                        </div>
                                        <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                                            <span class="text-sm font-semibold text-slate-900">Alertas</span>
                                            <span class="text-sm font-bold text-rose-600">3</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                                    <p class="text-xs font-semibold text-slate-500">Ações rápidas</p>
                                    <div class="mt-3 grid grid-cols-2 gap-3">
                                        <div class="rounded-xl bg-indigo-50 px-3 py-2">
                                            <p class="text-sm font-semibold text-slate-900">Nova venda</p>
                                            <p class="text-xs text-slate-600">Em poucos cliques</p>
                                        </div>
                                        <div class="rounded-xl bg-emerald-50 px-3 py-2">
                                            <p class="text-sm font-semibold text-slate-900">Entrada</p>
                                            <p class="text-xs text-slate-600">Atualize o estoque</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="lg:col-span-7">
                                <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs font-semibold text-slate-500">Produtos</p>
                                        <p class="text-xs font-semibold text-slate-500">Últimas movimentações</p>
                                    </div>

                                    <div class="mt-3 space-y-3">
                                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                                            <div class="flex items-center justify-between">
                                                <p class="text-sm font-semibold text-slate-900">Camiseta algodão</p>
                                                <p class="text-sm font-semibold text-slate-700">12 unidades</p>
                                            </div>
                                            <p class="mt-1 text-xs text-slate-600">Saída registrada em venda</p>
                                        </div>

                                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                                            <div class="flex items-center justify-between">
                                                <p class="text-sm font-semibold text-slate-900">Tênis casual</p>
                                                <p class="text-sm font-semibold text-slate-700">34 unidades</p>
                                            </div>
                                            <p class="mt-1 text-xs text-slate-600">Entrada de reposição</p>
                                        </div>

                                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                                            <div class="flex items-center justify-between">
                                                <p class="text-sm font-semibold text-slate-900">Mochila executiva</p>
                                                <p class="text-sm font-semibold text-slate-700">7 unidades</p>
                                            </div>
                                            <p class="mt-1 text-xs text-slate-600">Estoque baixo, atenção</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="pointer-events-none absolute -right-6 -top-8 hidden w-64 rotate-2 rounded-[1.5rem] bg-white p-4 shadow-xl ring-1 ring-slate-200 lg:block">
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs font-semibold text-slate-500">Relatórios</p>
                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Atualizado</span>
                                    </div>
                                    <div class="mt-3 space-y-2">
                                        <div class="h-2 w-3/4 rounded-full bg-slate-200"></div>
                                        <div class="h-2 w-2/3 rounded-full bg-slate-200"></div>
                                        <div class="h-2 w-5/6 rounded-full bg-slate-200"></div>
                                        <div class="mt-3 grid grid-cols-3 gap-2">
                                            <div class="h-10 rounded-xl bg-indigo-100"></div>
                                            <div class="h-10 rounded-xl bg-sky-100"></div>
                                            <div class="h-10 rounded-xl bg-emerald-100"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="pointer-events-none absolute -bottom-10 left-10 hidden w-72 -rotate-2 rounded-[1.5rem] bg-white p-4 shadow-xl ring-1 ring-slate-200 lg:block">
                                    <p class="text-xs font-semibold text-slate-500">Vendas</p>
                                    <div class="mt-3 space-y-3">
                                        <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                                            <span class="text-sm font-semibold text-slate-900">Ticket médio</span>
                                            <span class="text-sm font-bold text-slate-900">R$ 164</span>
                                        </div>
                                        <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                                            <span class="text-sm font-semibold text-slate-900">Itens vendidos</span>
                                            <span class="text-sm font-bold text-slate-900">78</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-3 gap-3 text-center text-xs font-semibold text-slate-500 sm:text-sm">
                        <div class="rounded-2xl bg-white py-3 shadow-sm ring-1 ring-slate-200">Simples</div>
                        <div class="rounded-2xl bg-white py-3 shadow-sm ring-1 ring-slate-200">Rápido</div>
                        <div class="rounded-2xl bg-white py-3 shadow-sm ring-1 ring-slate-200">Confiável</div>
                    </div>
                </div>
            </div>
        </section>

        <section id="beneficios" class="border-t border-slate-100 bg-slate-50/60">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <div class="grid gap-10 lg:grid-cols-12 lg:items-start">
                    <div class="lg:col-span-5">
                        <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                            Um sistema direto ao ponto para vender melhor
                        </h2>
                        <p class="mt-4 text-lg leading-relaxed text-slate-600">
                            Você organiza produtos, controla estoque e registra vendas em um fluxo único.
                            Com isso, o time ganha clareza, reduz retrabalho e toma decisões com base em números.
                        </p>

                        <div class="mt-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <p class="text-sm font-semibold text-slate-900">Para quem é</p>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                Lojas, distribuidoras, pequenas redes e negócios que precisam de organização sem complicação,
                                com foco em rotina operacional e acompanhamento gerencial.
                            </p>
                        </div>
                    </div>

                    <div class="lg:col-span-7">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M4 7h16M6 7v13h12V7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M9 11h6M9 15h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <p class="mt-4 text-base font-semibold text-slate-900">Controle de estoque</p>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                    Saiba o que entra, o que sai e o que precisa de reposição, com alertas e visão por produto.
                                </p>
                            </div>

                            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M7 7h10l1 4H6l1-4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                        <path d="M6 11l1 10h10l1-10" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                        <path d="M9 14h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <p class="mt-4 text-base font-semibold text-slate-900">Gestão de vendas</p>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                    Registre vendas com rapidez, mantenha histórico e acompanhe desempenho por períodos.
                                </p>
                            </div>

                            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-50 text-sky-700">
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M4 19V5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <path d="M8 19V9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <path d="M12 19V12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <path d="M16 19V7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <path d="M20 19V10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <p class="mt-4 text-base font-semibold text-slate-900">Relatórios</p>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                    Visualize indicadores essenciais para entender resultados e ajustar ações com segurança.
                                </p>
                            </div>

                            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-800">
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M7 8h10M7 12h10M7 16h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <path d="M5 6h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <p class="mt-4 text-base font-semibold text-slate-900">Organização de produtos</p>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                    Padronize cadastros, facilite buscas e mantenha seu catálogo pronto para vender mais.
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-base font-semibold text-slate-900">Acesse e comece agora</p>
                                    <p class="mt-1 text-sm text-slate-600">Seu time entra, organiza e já trabalha no mesmo fluxo</p>
                                </div>
                                <a href="{{ url('/admin/login') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                                    Entrar no sistema
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="recursos" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
            <div class="grid gap-12 lg:grid-cols-12 lg:items-start">
                <div class="lg:col-span-5">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                        Rotina simples, gestão clara
                    </h2>
                    <p class="mt-4 text-lg leading-relaxed text-slate-600">
                        Cada área do sistema foi pensada para reduzir etapas e facilitar o acompanhamento.
                        Assim, você mantém operação e gestão no mesmo ritmo.
                    </p>

                    <div class="mt-8 space-y-4">
                        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                            <p class="text-sm font-semibold text-slate-900">Cadastro inteligente de produtos</p>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                Estruture informações essenciais e encontre tudo rapidamente, mesmo com catálogos grandes.
                            </p>
                        </div>
                        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                            <p class="text-sm font-semibold text-slate-900">Movimentações rastreáveis</p>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                Entrada, saída e ajustes com histórico, facilitando auditoria interna e conferência.
                            </p>
                        </div>
                        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                            <p class="text-sm font-semibold text-slate-900">Visão gerencial por período</p>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                Compare resultados e acompanhe evolução com relatórios objetivos e fáceis de entender.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-7">
                    <div class="rounded-[2rem] bg-slate-50 p-6 ring-1 ring-slate-200">
                        <div class="flex items-start justify-between gap-6">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Área preparada para imagens reais</p>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                    Substitua os blocos abaixo por prints e mockups do seu sistema.
                                    O layout já está pronto para criar a sensação de produto em uso.
                                </p>
                            </div>
                            <div class="hidden sm:flex">
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">Pronto para mockups</span>
                            </div>
                        </div>

                        <div class="mt-8 grid gap-5 lg:grid-cols-12">
                            <div class="lg:col-span-7">
                                <div class="relative overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                                    <div class="flex items-center justify-between px-4 py-3 bg-slate-50 ring-1 ring-slate-100">
                                        <p class="text-xs font-semibold text-slate-600">Print do painel ou dashboard</p>
                                        <div class="flex items-center gap-2">
                                            <span class="h-2 w-2 rounded-full bg-slate-300"></span>
                                            <span class="h-2 w-2 rounded-full bg-slate-300"></span>
                                            <span class="h-2 w-2 rounded-full bg-slate-300"></span>
                                        </div>
                                    </div>
                                    <div class="aspect-[16/10] w-full bg-gradient-to-br from-slate-100 to-white"></div>
                                </div>
                            </div>

                            <div class="lg:col-span-5">
                                <div class="relative">
                                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                                        <div class="flex items-center justify-between px-4 py-3 bg-slate-50 ring-1 ring-slate-100">
                                            <p class="text-xs font-semibold text-slate-600">Print de cadastro</p>
                                            <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700">Formulário</span>
                                        </div>
                                        <div class="aspect-[4/5] w-full bg-gradient-to-br from-slate-100 to-white"></div>
                                    </div>

                                    <div class="absolute -bottom-6 -left-6 hidden w-56 overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-200 sm:block">
                                        <div class="flex items-center justify-between px-4 py-3 bg-slate-50 ring-1 ring-slate-100">
                                            <p class="text-xs font-semibold text-slate-600">Print de relatório</p>
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Gráfico</span>
                                        </div>
                                        <div class="aspect-[5/4] w-full bg-gradient-to-br from-slate-100 to-white"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-slate-600">
                                Dica: use imagens com fundo limpo, recorte consistente e boa resolução para reforçar o aspecto profissional.
                            </p>
                            <a href="{{ url('/admin/login') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                                Acessar agora
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="visao" class="border-t border-slate-100 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <div class="grid gap-10 lg:grid-cols-12 lg:items-center">
                    <div class="lg:col-span-6">
                        <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                            Uma experiência pensada para uso diário
                        </h2>
                        <p class="mt-4 text-lg leading-relaxed text-slate-600">
                            Interface com boa leitura, navegação previsível e foco no que importa.
                            Seu time aprende rápido e mantém o controle sem depender de planilhas paralelas.
                        </p>

                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200">
                                <p class="text-sm font-semibold text-slate-900">Fluxos claros</p>
                                <p class="mt-2 text-sm text-slate-600">Processos organizados do cadastro ao relatório</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200">
                                <p class="text-sm font-semibold text-slate-900">Decisão com dados</p>
                                <p class="mt-2 text-sm text-slate-600">Indicadores para entender e agir com segurança</p>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-6">
                        <div class="relative">
                            <div class="absolute -inset-6 -z-10 rounded-[2.5rem] bg-gradient-to-br from-indigo-50 via-white to-slate-50"></div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                                    <p class="text-xs font-semibold text-slate-500">Espaço para mockup</p>
                                    <div class="mt-3 aspect-[16/10] rounded-xl bg-gradient-to-br from-slate-100 to-white"></div>
                                    <p class="mt-3 text-sm font-semibold text-slate-900">Tela de vendas</p>
                                    <p class="mt-1 text-sm text-slate-600">Checkout rápido e registro organizado</p>
                                </div>

                                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                                    <p class="text-xs font-semibold text-slate-500">Espaço para mockup</p>
                                    <div class="mt-3 aspect-[16/10] rounded-xl bg-gradient-to-br from-slate-100 to-white"></div>
                                    <p class="mt-3 text-sm font-semibold text-slate-900">Tela de estoque</p>
                                    <p class="mt-1 text-sm text-slate-600">Movimentações e alertas de reposição</p>
                                </div>
                            </div>

                            <div class="mt-6 rounded-2xl bg-slate-900 p-6 text-white shadow-sm">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-base font-semibold">Pronto para começar</p>
                                        <p class="mt-1 text-sm text-slate-200">Acesse o painel e organize sua operação hoje</p>
                                    </div>
                                    <a href="{{ url('/admin/login') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-sm hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-slate-900">
                                        Entrar no sistema
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-100 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-white">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M7 6h10M7 12h10M7 18h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ config('app.name', 'ERP Estoque e Vendas') }}</p>
                        <p class="text-sm text-slate-600">Organização simples para vender com controle</p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-6">
                    <a href="#beneficios" class="text-sm font-medium text-slate-700 hover:text-slate-900">Benefícios</a>
                    <a href="#recursos" class="text-sm font-medium text-slate-700 hover:text-slate-900">Recursos</a>
                    <a href="#visao" class="text-sm font-medium text-slate-700 hover:text-slate-900">Visão do produto</a>
                    <a href="{{ url('/admin/login') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                        Entrar no sistema
                    </a>
                </div>
            </div>

            <div class="mt-8 flex flex-col gap-2 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">
                    © {{ date('Y') }} {{ config('app.name', 'ERP Estoque e Vendas') }}. Todos os direitos reservados.
                </p>
                <p class="text-xs text-slate-500">
                    Feito para apoiar a rotina de vendas e estoque com clareza e consistência.
                </p>
            </div>
        </div>
    </footer>
</div>
</body>
</html>
