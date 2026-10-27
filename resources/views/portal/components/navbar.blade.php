<header class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6 sm:py-4 lg:px-8">
        <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-2 sm:gap-3">
            <img src="{{ asset('assets/img/vendix.png') }}" alt="Vendix" class="h-9 w-9 shrink-0 sm:h-11 sm:w-11" />
            <span class="truncate text-lg font-extrabold tracking-tight text-violet-800 sm:text-xl">Vendix</span>
        </a>

        <nav class="hidden items-center gap-8 text-base font-semibold text-slate-700 lg:flex">
            <a href="#solucoes" class="transition hover:text-violet-700">O Vendix</a>
            <a href="#modulos" class="transition hover:text-violet-700">Recursos</a>
            <a href="#recursos" class="transition hover:text-violet-700">Na prática</a>
            <a href="#faq" class="transition hover:text-violet-700">Dúvidas</a>
        </nav>

        <div class="flex items-center gap-2 sm:gap-3">
            <a href="{{ url('/admin/login') }}" class="hidden rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold text-slate-800 transition hover:border-violet-400 hover:text-violet-700 sm:inline-flex sm:px-5 sm:py-2.5">
                Entrar
            </a>
            <a href="#contato" class="hidden rounded-xl bg-violet-700 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-violet-800 sm:inline-flex sm:px-5 sm:py-2.5">
                Falar conosco
            </a>
            <button
                type="button"
                id="portal-menu-toggle"
                class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-300 text-slate-800 lg:hidden"
                aria-expanded="false"
                aria-controls="portal-mobile-nav"
                aria-label="Abrir menu"
            >
                <svg id="portal-menu-icon-open" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
                <svg id="portal-menu-icon-close" viewBox="0 0 24 24" class="hidden h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </div>
    </div>

    <nav id="portal-mobile-nav" class="hidden border-t border-slate-200 bg-white lg:hidden" hidden>
        <div class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-3 sm:px-6">
            <a href="#solucoes" class="rounded-lg px-3 py-3 text-base font-semibold text-slate-800 hover:bg-slate-50">O Vendix</a>
            <a href="#modulos" class="rounded-lg px-3 py-3 text-base font-semibold text-slate-800 hover:bg-slate-50">Recursos</a>
            <a href="#recursos" class="rounded-lg px-3 py-3 text-base font-semibold text-slate-800 hover:bg-slate-50">Na prática</a>
            <a href="#faq" class="rounded-lg px-3 py-3 text-base font-semibold text-slate-800 hover:bg-slate-50">Dúvidas</a>
            <a href="{{ url('/admin/login') }}" class="mt-2 rounded-xl border border-slate-300 px-3 py-3 text-center text-sm font-bold text-slate-800">Entrar</a>
            <a href="#contato" class="rounded-xl bg-violet-700 px-3 py-3 text-center text-sm font-bold text-white">Falar conosco</a>
        </div>
    </nav>
</header>
