<header class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <a href="{{ url('/') }}" class="flex items-center gap-3">
            <img src="{{ asset('assets/img/vendix.png') }}" alt="Vendix" class="h-11 w-11" />
            <span class="text-xl font-extrabold tracking-tight text-violet-800">Vendix</span>
        </a>

        <nav class="hidden items-center gap-8 text-base font-semibold text-slate-700 lg:flex">
            <a href="#solucoes" class="transition hover:text-violet-700">Solucoes</a>
            <a href="#segmentos" class="transition hover:text-violet-700">Segmentos</a>
            <a href="#precos" class="transition hover:text-violet-700">Planos</a>
            <a href="#ajuda" class="transition hover:text-violet-700">Ajuda</a>
        </nav>

        <div class="flex items-center gap-3">
            <a href="{{ url('/admin/login') }}" class="hidden rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-800 transition hover:border-violet-400 hover:text-violet-700 sm:inline-flex">
                Entrar no Vendix Web
            </a>
            <a href="#contato" class="rounded-xl bg-violet-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-violet-800">
                Assinar o Vendix
            </a>
        </div>
    </div>
</header>
