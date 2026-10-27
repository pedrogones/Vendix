<footer class="overflow-x-hidden border-t border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div class="grid gap-8 sm:gap-10 md:grid-cols-2 lg:grid-cols-4">
            <div class="min-w-0">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-3">
                    <img src="{{ asset('assets/img/vendix.png') }}" alt="Vendix" class="h-10 w-10 sm:h-11 sm:w-11" />
                    <span class="text-xl font-extrabold text-violet-800 sm:text-2xl">Vendix</span>
                </a>
                <p class="mt-4 max-w-xs text-sm text-slate-600 sm:text-base">
                    Sistema de gestão para loja: balcão, estoque, catálogo e relatórios com informação real.
                </p>
            </div>

            <div>
                <p class="text-lg font-extrabold text-slate-900 sm:text-xl">Produto</p>
                <ul class="mt-3 space-y-2 text-base text-slate-600 sm:mt-4 sm:text-lg">
                    <li><a href="#modulos" class="hover:text-violet-700">Ponto de venda</a></li>
                    <li><a href="#modulos" class="hover:text-violet-700">Estoque</a></li>
                    <li><a href="#recursos" class="hover:text-violet-700">Cadastro assistido</a></li>
                    <li><a href="#recursos" class="hover:text-violet-700">Relatórios</a></li>
                </ul>
            </div>

            <div>
                <p class="text-lg font-extrabold text-slate-900 sm:text-xl">Canais</p>
                <ul class="mt-3 space-y-2 text-base text-slate-600 sm:mt-4 sm:text-lg">
                    <li><a href="#recursos" class="hover:text-violet-700">Vitrine pública</a></li>
                    <li><a href="#recursos" class="hover:text-violet-700">Pedido por conversa</a></li>
                    <li><a href="#faq" class="hover:text-violet-700">Pagamento no atendimento</a></li>
                </ul>
            </div>

            <div>
                <p class="text-lg font-extrabold text-slate-900 sm:text-xl">Acesso</p>
                <ul class="mt-3 space-y-2 text-base text-slate-600 sm:mt-4 sm:text-lg">
                    <li><a href="{{ url('/admin/login') }}" class="hover:text-violet-700">Entrar no painel</a></li>
                    <li><a href="#contato" class="hover:text-violet-700">Falar conosco</a></li>
                    <li><a href="#faq" class="hover:text-violet-700">Perguntas frequentes</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-10 border-t border-slate-200 pt-6 sm:mt-12">
            <p class="text-sm text-slate-500">
                &copy; {{ date('Y') }} Vendix{{ env('CNPJ_OWNER') ? ' - ' . env('CNPJ_OWNER') : '' }}. Todos os direitos reservados.
            </p>
        </div>
    </div>
</footer>
