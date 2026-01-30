<section id="solucoes" class="relative overflow-hidden bg-gradient-to-b from-violet-100 via-white to-amber-50">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-violet-300/50 blur-3xl"></div>
        <div class="absolute right-0 top-20 h-80 w-80 translate-x-1/3 rounded-full bg-amber-200/60 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-sky-200/50 blur-3xl"></div>
    </div>

    <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:py-20 lg:px-8">
        <div class="space-y-6 animate-fade-up">
            <p class="inline-flex items-center gap-2 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm ring-1 ring-slate-200">
                Vendix conecta vendas, estoque e financeiro
            </p>
            <h1 class="text-4xl font-bold leading-tight text-slate-900 sm:text-5xl">
                O sistema de Controle de Estoque
                <span class="text-violet-700">que conversa com o restante</span>
                do seu negocio
            </h1>
            <p class="text-lg text-slate-600">
                Vendas, compras, notas e financeiro sincronizados. Chega de ajustar numeros manualmente.
                O Vendix organiza sua operacao em tempo real.
            </p>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <a href="#contato" class="rounded-full bg-gradient-to-r from-amber-400 to-amber-300 px-6 py-3 text-sm font-semibold text-slate-900 shadow-md hover:brightness-105">
                    Quero conhecer
                </a>
                <a href="{{ url('/admin/login') }}" class="rounded-full border border-slate-300 bg-white/90 px-6 py-3 text-sm font-semibold text-slate-900 shadow-sm hover:border-slate-400">
                    Testar gratis por 7 dias
                </a>
            </div>
            <div class="flex flex-wrap gap-4 text-sm text-slate-600">
                <span class="inline-flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Relatorios prontos para decisao
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    PDV rapido e intuitivo
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Estoque sempre atualizado
                </span>
            </div>
        </div>

        <div class="relative">
            <div class="absolute -inset-6 rounded-[2.5rem] bg-white/70 shadow-[0_60px_140px_rgba(99,102,241,0.25)]"></div>
            <div class="relative rounded-[2rem] border border-slate-200/70 bg-white/90 p-5 shadow-xl">
                <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-rose-400"></span>
                        <span class="h-3 w-3 rounded-full bg-amber-300"></span>
                        <span class="h-3 w-3 rounded-full bg-emerald-400"></span>
                    </div>
                    <span class="text-xs font-semibold text-slate-500">Painel em tempo real</span>
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-12">
                    <div class="lg:col-span-7">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-xs font-semibold text-slate-500">Resumo do dia</p>
                            <div class="mt-4 space-y-3">
                                <div class="flex items-center justify-between rounded-xl bg-gradient-to-r from-emerald-50 to-white px-4 py-3">
                                    <span class="text-sm font-semibold text-slate-900">Faturamento</span>
                                    <span class="text-sm font-bold text-emerald-600">R$ 12.840</span>
                                </div>
                                <div class="flex items-center justify-between rounded-xl bg-gradient-to-r from-violet-50 to-white px-4 py-3">
                                    <span class="text-sm font-semibold text-slate-900">Ticket medio</span>
                                    <span class="text-sm font-bold text-slate-900">R$ 186</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-sky-50 p-4">
                            <p class="text-xs font-semibold text-slate-500">Movimentacao de estoque</p>
                            <div class="mt-4 h-32 rounded-xl bg-[linear-gradient(90deg,rgba(99,102,241,0.15)_0%,rgba(14,165,233,0.25)_50%,rgba(34,197,94,0.2)_100%)]"></div>
                        </div>
                    </div>
                    <div class="lg:col-span-5">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-xs font-semibold text-slate-500">Alertas inteligentes</p>
                            <div class="mt-4 space-y-3">
                                <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2">
                                    <p class="text-sm font-semibold text-slate-900">Estoque baixo</p>
                                    <p class="text-xs text-slate-600">6 produtos precisam reposicao</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                    <p class="text-sm font-semibold text-slate-900">Pedidos hoje</p>
                                    <p class="text-xs text-slate-600">24 vendas finalizadas</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-xs font-semibold text-slate-500">Status financeiro</p>
                            <div class="mt-4 space-y-2">
                                <div class="h-2 w-full rounded-full bg-slate-100">
                                    <div class="h-2 w-3/4 rounded-full bg-gradient-to-r from-violet-500 to-sky-400"></div>
                                </div>
                                <p class="text-xs text-slate-500">Metas mensais 78%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="absolute -bottom-10 left-10 hidden w-64 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl lg:block">
                <p class="text-xs font-semibold text-slate-500">Indicadores</p>
                <div class="mt-3 space-y-2">
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                        <span class="text-xs font-semibold text-slate-900">Produtos ativos</span>
                        <span class="text-xs font-bold text-slate-900">1.320</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                        <span class="text-xs font-semibold text-slate-900">NFs emitidas</span>
                        <span class="text-xs font-bold text-slate-900">98</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
