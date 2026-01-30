<footer class="bg-slate-950 text-slate-200">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('assets/img/vendix.png') }}" alt="Vendix" class="h-10 w-10" />
                <div>
                    <p class="text-sm font-semibold text-white">Vendix</p>
                    <p class="text-sm text-slate-400">Organizacao simples para vender com controle.</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-sm text-slate-300">
                <a href="#solucoes" class="hover:text-white">Solucoes</a>
                <a href="#modulos" class="hover:text-white">Recursos</a>
                <a href="#faq" class="hover:text-white">FAQ</a>
                <a href="{{ url('/admin/login') }}" class="rounded-full border border-white/20 px-4 py-2 text-sm font-semibold text-white hover:border-white/40">
                    Acessar
                </a>
            </div>
        </div>
        <div class="mt-6 border-t border-white/10 pt-4 text-xs text-slate-400">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p> {{ date('Y') }} Vendix <br>
                 {{env('CNPJ_OWNER')}} - Todos os direitos reservados.</p>
                <p>Feito para apoiar vendas, estoque e financeiro com clareza.</p>
            </div>
        </div>
    </div>
</footer>
