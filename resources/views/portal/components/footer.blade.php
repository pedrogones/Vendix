<footer class="border-t border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-[180px_repeat(5,minmax(0,1fr))]">
            <div>
                <a href="{{ url('/') }}" class="inline-flex items-center gap-3">
                    <img src="{{ asset('assets/img/vendix.png') }}" alt="Vendix" class="h-11 w-11" />
                    <span class="text-2xl font-extrabold text-violet-800">Vendix</span>
                </a>
            </div>

            <div>
                <p class="text-xl font-extrabold text-slate-900">Venda</p>
                <ul class="mt-4 space-y-2 text-lg text-slate-600">
                    <li><a href="#modulos" class="hover:text-violet-700">Sistema PDV</a></li>
                    <li><a href="#solucoes" class="hover:text-violet-700">Pedidos online</a></li>
                    <li><a href="#solucoes" class="hover:text-violet-700">WhatsApp</a></li>
                    <li><a href="#segmentos" class="hover:text-violet-700">Recibos</a></li>
                    <li><a href="#segmentos" class="hover:text-violet-700">Delivery</a></li>
                </ul>
            </div>

            <div>
                <p class="text-xl font-extrabold text-slate-900">Gerencie</p>
                <ul class="mt-4 space-y-2 text-lg text-slate-600">
                    <li><a href="#modulos" class="hover:text-violet-700">Estoque</a></li>
                    <li><a href="#modulos" class="hover:text-violet-700">Produtos</a></li>
                    <li><a href="#modulos" class="hover:text-violet-700">Pedidos</a></li>
                    <li><a href="#modulos" class="hover:text-violet-700">Clientes</a></li>
                    <li><a href="#modulos" class="hover:text-violet-700">Fluxo de caixa</a></li>
                </ul>
            </div>

            <div>
                <p class="text-xl font-extrabold text-slate-900">Fidelize</p>
                <ul class="mt-4 space-y-2 text-lg text-slate-600">
                    <li><a href="#segmentos" class="hover:text-violet-700">Catalogo</a></li>
                    <li><a href="#segmentos" class="hover:text-violet-700">Cardapio digital</a></li>
                    <li><a href="#segmentos" class="hover:text-violet-700">Controle de fiado</a></li>
                    <li><a href="#segmentos" class="hover:text-violet-700">Vitrine virtual</a></li>
                    <li><a href="#precos" class="hover:text-violet-700">Pagamentos</a></li>
                </ul>
            </div>

            <div>
                <p class="text-xl font-extrabold text-slate-900">Segmentos</p>
                <ul class="mt-4 space-y-2 text-lg text-slate-600">
                    <li><a href="#segmentos" class="hover:text-violet-700">Restaurantes</a></li>
                    <li><a href="#segmentos" class="hover:text-violet-700">Bares</a></li>
                    <li><a href="#segmentos" class="hover:text-violet-700">Lanchonetes</a></li>
                    <li><a href="#segmentos" class="hover:text-violet-700">Mercados</a></li>
                    <li><a href="#segmentos" class="hover:text-violet-700">Lojas de moda</a></li>
                </ul>
            </div>

            <div>
                <p class="text-xl font-extrabold text-slate-900">Vendix</p>
                <ul class="mt-4 space-y-2 text-lg text-slate-600">
                    <li><a href="{{ url('/admin/login') }}" class="hover:text-violet-700">Entrar no Vendix Web</a></li>
                    <li><a href="#contato" class="hover:text-violet-700">Assinar o Vendix</a></li>
                    <li><a href="#faq" class="hover:text-violet-700">Perguntas frequentes</a></li>
                    <li><a href="#ajuda" class="hover:text-violet-700">Conteudos e guias</a></li>
                    <li><a href="#" class="hover:text-violet-700">Trabalhe conosco</a></li>
                </ul>
                <div class="mt-4 space-y-2 text-lg font-bold text-violet-700">
                    <p>Portugues</p>
                    <p>English</p>
                    <p>Espanol</p>
                </div>
            </div>
        </div>

        <div class="mt-12 border-t border-slate-200 pt-6">
            <div class="flex flex-col gap-4 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
                <p>
                    &copy; {{ date('Y') }} Vendix{{ env('CNPJ_OWNER') ? ' - ' . env('CNPJ_OWNER') : '' }}. Todos os direitos reservados.
                </p>
                <div class="flex items-center gap-3 text-slate-400">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-300">in</span>
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-300">ig</span>
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-300">yt</span>
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-300">tt</span>
                </div>
            </div>
        </div>
    </div>
</footer>
