<section id="modulos" class="overflow-x-hidden bg-slate-50 pb-12 sm:pb-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-6 rounded-[1.5rem] border border-violet-300 bg-white p-5 shadow-sm sm:gap-8 sm:rounded-[2rem] sm:p-6 lg:grid-cols-2 lg:items-center lg:p-10">
            <div class="min-w-0">
                <h2 class="text-2xl font-extrabold leading-tight text-slate-900 sm:text-4xl">
                    <span class="text-violet-700">Operação</span> do balcão ao relatório
                </h2>
                <p class="mt-4 text-base leading-relaxed text-slate-600 sm:mt-5 sm:text-lg">
                    Tudo gira em torno da loja: produtos, categorias, clientes, usuários com permissão,
                    vendas confirmadas, recebimentos registrados e números que batem com o estoque.
                </p>

                <ul class="mt-6 space-y-3 text-base text-slate-800 sm:mt-7 sm:space-y-4 sm:text-lg">
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-700">&#10003;</span>
                        <span>PDV com itens, desconto e confirmação segura</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-700">&#10003;</span>
                        <span>Promoções com regra única no balcão e na vitrine</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-700">&#10003;</span>
                        <span>Dashboard e relatório de vendas recebidas</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-700">&#10003;</span>
                        <span>Cancelamento com devolução de estoque e estorno</span>
                    </li>
                </ul>

                <a href="{{ url('/admin/login') }}" class="mt-6 inline-flex w-full justify-center rounded-xl bg-violet-700 px-8 py-3 text-base font-bold text-white transition hover:bg-violet-800 sm:mt-8 sm:w-auto">
                    Acessar o painel
                </a>
            </div>

            <div class="min-w-0 rounded-3xl bg-slate-100 p-4 sm:p-5">
                <div class="mx-auto max-w-sm rounded-3xl bg-white p-4 shadow-lg sm:p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Resumo do dia</p>
                    <p class="mt-3 text-xl font-semibold text-slate-800 sm:text-2xl">Vendas recebidas</p>
                    <div class="mt-5 space-y-3 text-sm text-slate-600">
                        <div class="flex items-center justify-between gap-2 rounded-xl bg-slate-50 px-3 py-2">
                            <span>Confirmadas e pagas</span>
                            <strong class="shrink-0 text-slate-900">no relatório</strong>
                        </div>
                        <div class="flex items-center justify-between gap-2 rounded-xl bg-slate-50 px-3 py-2">
                            <span>Estoque</span>
                            <strong class="shrink-0 text-slate-900">histórico</strong>
                        </div>
                        <div class="flex flex-col gap-1 rounded-xl bg-violet-50 px-3 py-2 text-violet-800 sm:flex-row sm:items-center sm:justify-between">
                            <span>Recebimento</span>
                            <strong class="text-sm">dinheiro · Pix · cartão</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
