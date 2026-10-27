<section id="ajuda" class="overflow-x-hidden bg-white py-12 sm:py-20">
    <div id="contato"></div>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-2xl font-extrabold text-slate-900 sm:text-4xl">Pronto para organizar a loja</h2>
            <p class="mx-auto mt-3 max-w-2xl text-base text-slate-600 sm:mt-4 sm:text-lg">
                O Vendix é o sistema da equipe: vender no balcão, manter estoque coerente e enxergar o que já foi recebido.
            </p>
            <div class="mx-auto mt-4 h-1 w-24 rounded-full bg-violet-200 sm:w-40"></div>
        </div>

        <div class="mt-8 grid gap-4 sm:mt-10 sm:gap-6 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">1. Cadastre</p>
                <h3 class="mt-3 text-xl font-extrabold leading-tight text-slate-900 sm:text-2xl">Produtos e estoque</h3>
                <p class="mt-3 text-sm text-slate-600 sm:text-base">Monte o catálogo com categorias, preços e leitura de código quando fizer sentido.</p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">2. Venda</p>
                <h3 class="mt-3 text-xl font-extrabold leading-tight text-slate-900 sm:text-2xl">Balcão com recebimento</h3>
                <p class="mt-3 text-sm text-slate-600 sm:text-base">Finalize a venda, registre o meio de pagamento e deixe o estoque acompanhar.</p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">3. Acompanhe</p>
                <h3 class="mt-3 text-xl font-extrabold leading-tight text-slate-900 sm:text-2xl">Números que batem</h3>
                <p class="mt-3 text-sm text-slate-600 sm:text-base">Use dashboard e relatório para ver o que foi vendido e efetivamente recebido.</p>
            </article>
        </div>

        <div class="mt-10 rounded-[1.5rem] bg-violet-700 px-5 py-8 text-white sm:mt-16 sm:rounded-[2rem] sm:px-8 sm:py-10">
            <div class="mx-auto max-w-3xl text-center">
                <h3 class="text-2xl font-extrabold sm:text-3xl lg:text-4xl">Quer ver o Vendix em ação?</h3>
                <p class="mt-3 text-base text-violet-100 sm:mt-4 sm:text-lg">
                    Entre no painel com sua conta ou fale com a gente para conhecer a operação — sem números genéricos de “milhares de lojas”.
                </p>
                <div class="mt-6 flex flex-col items-stretch justify-center gap-3 sm:mt-8 sm:flex-row sm:items-center">
                    <a href="{{ url('/admin/login') }}" class="rounded-xl bg-white px-6 py-3 text-base font-bold text-violet-800 transition hover:bg-violet-50 sm:px-8">
                        Entrar no sistema
                    </a>
                    <a href="mailto:{{ config('mail.from.address', 'contato@vendix.app') }}" class="rounded-xl border border-white/40 px-6 py-3 text-base font-bold text-white transition hover:bg-white/10 sm:px-8">
                        Contato
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
