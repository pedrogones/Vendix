<header class="border-b bg-gradient-to-r from-violet-100 via-white to-violet-100 border-slate-200" style="height: 8rem">
    <div class="mx-auto flex  items-center justify-between py-4 lg:px-8" style="background-color: black">
    </div>
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8" style="height: 6rem">
        <div class="flex items-center gap-3">
            <img src="{{ asset('assets/img/vendix.png') }}" alt="Vendix" class="w-auto" style="height: 7rem" />
        </div>

        <nav class="hidden items-center gap-6 text-sm font-medium text-slate-600 lg:flex">
            <a href="#solucoes" class="hover:text-slate-900">Solucoes</a>
            <a href="#modulos" class="hover:text-slate-900">CRM</a>
            <a href="#contadores" class="hover:text-slate-900">Contadores</a>
            <a href="#bpo" class="hover:text-slate-900">BPO Financeiro</a>
            <a href="#segmentos" class="hover:text-slate-900">Segmentos</a>
            <a href="#precos" class="hover:text-slate-900">Precos</a>
        </nav>

        <div class="flex items-center gap-3">
            <a href="#contato" class="rounded-full bg-amber-400 px-5 py-2 text-sm font-semibold text-slate-900 shadow-sm hover:bg-amber-300">
                Quero conhecer
            </a>
            <a href="{{ url('/admin/login') }}" class="rounded-full border border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-900 hover:border-slate-400">
                Teste Gratis
            </a>
        </div>
    </div>
</header>
