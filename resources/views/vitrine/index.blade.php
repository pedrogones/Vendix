<!doctype html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $company['name'] }} | Vitrine</title>
    <meta name="description" content="Catálogo da {{ $company['name'] }}. Peça pelo chat — retirada ou entrega." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --papel: #F3F1EC;
            --papel-2: #EAE6DE;
            --tinta: #1A1F16;
            --tinta-soft: #3D4538;
            --folha: #2F5D50;
            --folha-deep: #23483E;
            --citrico: #D4A017;
            --borda: rgba(26, 31, 22, 0.12);
            --alerta: #8B3A2F;
            --radius: 14px;
            --max: 1120px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "DM Sans", system-ui, sans-serif;
            color: var(--tinta);
            background:
                radial-gradient(ellipse 80% 50% at 10% -10%, rgba(47, 93, 80, 0.14), transparent 55%),
                radial-gradient(ellipse 60% 40% at 100% 0%, rgba(212, 160, 23, 0.12), transparent 45%),
                var(--papel);
            min-height: 100vh;
        }

        img { display: block; max-width: 100%; }
        button, input { font: inherit; }
        a { color: inherit; text-decoration: none; }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .wrap {
            width: min(100% - 2rem, var(--max));
            margin-inline: auto;
        }

        .hero {
            position: relative;
            isolation: isolate;
            min-height: min(72vh, 560px);
            display: grid;
            align-items: end;
            overflow: hidden;
            background: #1a2a24;
        }

        .hero__media {
            position: absolute;
            inset: 0;
            z-index: -2;
            background:
                radial-gradient(circle at 70% 30%, rgba(212, 160, 23, 0.22), transparent 40%),
                linear-gradient(135deg, #1a2a24 0%, #2f5d50 55%, #1a1f16 100%);
        }

        .hero__media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: saturate(0.85) brightness(0.72);
        }

        .hero__veil {
            position: absolute;
            inset: 0;
            z-index: -1;
            background: linear-gradient(180deg, rgba(26, 31, 22, 0.25) 0%, rgba(26, 31, 22, 0.78) 72%, rgba(26, 31, 22, 0.92) 100%);
        }

        .hero__content {
            padding: 3.5rem 0 2.75rem;
            color: #f7f4ee;
            max-width: 36rem;
            animation: rise 0.7s ease both;
        }

        .hero__brand {
            font-family: Fraunces, Georgia, serif;
            font-weight: 700;
            font-size: clamp(2.6rem, 8vw, 4.25rem);
            line-height: 0.95;
            letter-spacing: -0.02em;
            margin: 0 0 0.85rem;
        }

        .hero__lead {
            margin: 0 0 1.5rem;
            font-size: 1.05rem;
            line-height: 1.45;
            color: rgba(247, 244, 238, 0.88);
            max-width: 28rem;
        }

        .hero__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            min-height: 48px;
            padding: 0.7rem 1.25rem;
            border-radius: 999px;
            border: 1px solid transparent;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: transform 0.18s ease, background 0.18s ease, border-color 0.18s ease;
        }

        .btn:active { transform: translateY(1px); }

        .btn--primary {
            background: var(--citrico);
            color: var(--tinta);
        }

        .btn--primary:hover { background: #e0b01f; }

        .btn--ghost {
            background: transparent;
            border-color: rgba(247, 244, 238, 0.35);
            color: #f7f4ee;
        }

        .btn--ghost:hover {
            border-color: rgba(247, 244, 238, 0.7);
            background: rgba(247, 244, 238, 0.08);
        }

        .section {
            padding: 2.25rem 0 0.5rem;
        }

        .section__head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.1rem;
        }

        .section__title {
            font-family: Fraunces, Georgia, serif;
            font-size: clamp(1.45rem, 3vw, 1.85rem);
            font-weight: 600;
            margin: 0;
            letter-spacing: -0.01em;
        }

        .section__hint {
            font-size: 0.82rem;
            color: var(--tinta-soft);
            font-weight: 500;
        }

        .offers {
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: minmax(200px, 240px);
            gap: 0.9rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        .offer-card,
        .product {
            scroll-snap-align: start;
            background: #fff;
            border: 1px solid var(--borda);
            border-radius: var(--radius);
            overflow: hidden;
            cursor: pointer;
            text-align: left;
            padding: 0;
            display: flex;
            flex-direction: column;
            width: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .offer-card:hover,
        .product:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(26, 31, 22, 0.1);
        }

        .offer-card__img,
        .product__img {
            aspect-ratio: 4 / 3;
            background: var(--papel-2);
            position: relative;
        }

        .product__img {
            aspect-ratio: 5 / 4;
        }

        .offer-card__img img,
        .product__img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .badge-off {
            position: absolute;
            top: 0.6rem;
            left: 0.6rem;
            background: var(--citrico);
            color: var(--tinta);
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 0.28rem 0.5rem;
            border-radius: 6px;
        }

        .badge-out {
            position: absolute;
            top: 0.6rem;
            left: 0.6rem;
            background: var(--alerta);
            color: #fff;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 0.28rem 0.5rem;
            border-radius: 6px;
        }

        .badge-off + .badge-out {
            left: auto;
            right: 0.6rem;
        }

        .offer-card__body,
        .product__body {
            padding: 0.85rem 0.95rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            flex: 1;
        }

        .offer-card__name,
        .product__name {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
            line-height: 1.25;
        }

        .product__cat {
            margin: 0;
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--tinta-soft);
        }

        .price-old {
            margin: 0;
            font-size: 0.75rem;
            color: var(--tinta-soft);
            text-decoration: line-through;
        }

        .price-now {
            margin: 0.1rem 0 0;
            font-family: Fraunces, Georgia, serif;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--folha);
        }

        .product__ask {
            margin-top: auto;
            padding-top: 0.65rem;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--folha);
        }

        .toolbar {
            display: grid;
            gap: 0.85rem;
            margin-bottom: 1.15rem;
        }

        .search {
            position: relative;
        }

        .search input {
            width: 100%;
            min-height: 48px;
            border-radius: 12px;
            border: 1px solid var(--borda);
            background: #fff;
            padding: 0.7rem 1rem 0.7rem 2.7rem;
            color: var(--tinta);
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .search input:focus {
            border-color: var(--folha);
            box-shadow: 0 0 0 3px rgba(47, 93, 80, 0.15);
        }

        .search__icon {
            position: absolute;
            left: 0.95rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--tinta-soft);
            pointer-events: none;
        }

        .chips {
            display: flex;
            gap: 0.45rem;
            overflow-x: auto;
            padding-bottom: 0.15rem;
            scrollbar-width: none;
        }

        .chips::-webkit-scrollbar { display: none; }

        .chip {
            flex: 0 0 auto;
            border: 1px solid var(--borda);
            background: transparent;
            color: var(--tinta-soft);
            border-radius: 999px;
            padding: 0.45rem 0.9rem;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
        }

        .chip:hover {
            border-color: var(--folha);
            color: var(--folha);
        }

        .chip[aria-pressed="true"] {
            background: var(--folha);
            border-color: var(--folha);
            color: #fff;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
        }

        @media (min-width: 720px) {
            .grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
            .toolbar { grid-template-columns: 1fr; }
        }

        @media (min-width: 1024px) {
            .grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }

        .empty {
            margin-top: 1rem;
            padding: 2.5rem 1.25rem;
            text-align: center;
            border: 1px dashed var(--borda);
            border-radius: var(--radius);
            background: rgba(255, 255, 255, 0.55);
            color: var(--tinta-soft);
        }

        .hidden { display: none !important; }

        .pager {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.45rem;
            margin: 1.5rem 0 0.5rem;
        }

        .page-btn {
            min-width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 1px solid var(--borda);
            background: #fff;
            color: var(--folha);
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            padding: 0 0.75rem;
        }

        .page-btn:disabled { opacity: 0.35; cursor: not-allowed; }
        .page-btn.is-active {
            background: var(--folha);
            border-color: var(--folha);
            color: #fff;
        }

        .site-footer {
            margin-top: 3rem;
            padding: 2rem 0 6.5rem;
            border-top: 1px solid var(--borda);
            color: var(--tinta-soft);
            font-size: 0.88rem;
        }

        .site-footer strong {
            color: var(--tinta);
            font-family: Fraunces, Georgia, serif;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .site-footer p { margin: 0.35rem 0; }
        .site-footer .powered {
            margin-top: 1rem;
            font-size: 0.78rem;
            opacity: 0.8;
        }

        .chat-fab {
            position: fixed;
            right: 1.25rem;
            bottom: 1.25rem;
            z-index: 50;
            width: 58px;
            height: 58px;
            border-radius: 50%;
            border: none;
            background: var(--folha);
            color: #fff;
            box-shadow: 0 10px 28px rgba(47, 93, 80, 0.35);
            cursor: pointer;
            display: grid;
            place-items: center;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .chat-fab:hover {
            background: var(--folha-deep);
            transform: scale(1.04);
        }

        .chat-panel {
            position: fixed;
            right: 1.25rem;
            bottom: 5.5rem;
            z-index: 50;
            width: min(calc(100vw - 2rem), 360px);
            background: #fff;
            border: 1px solid var(--borda);
            border-radius: 18px;
            box-shadow: 0 18px 48px rgba(26, 31, 22, 0.18);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            opacity: 0;
            transform: translateY(12px) scale(0.98);
            pointer-events: none;
            transition: opacity 0.25s ease, transform 0.25s ease;
        }

        .chat-panel.is-open {
            opacity: 1;
            transform: none;
            pointer-events: auto;
        }

        .chat-panel__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.95rem 1rem;
            background: var(--folha);
            color: #fff;
        }

        .chat-panel__head p { margin: 0; }

        .chat-panel__eyebrow {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            opacity: 0.8;
            font-weight: 600;
        }

        .chat-panel__title {
            font-family: Fraunces, Georgia, serif;
            font-size: 1.05rem;
            font-weight: 600;
        }

        .chat-panel__close {
            border: none;
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
        }

        .chat-panel__msgs {
            height: 280px;
            overflow-y: auto;
            padding: 1rem;
            background: var(--papel);
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }

        .chatbot-bubble {
            max-width: 85%;
            min-width: 0;
            padding: 0.65rem 0.8rem;
            border-radius: 14px;
            font-size: 0.88rem;
            line-height: 1.4;
            overflow-wrap: anywhere;
            word-break: break-word;
            white-space: pre-wrap;
        }

        .chatbot-bubble--bot {
            background: #fff;
            border: 1px solid var(--borda);
            border-top-left-radius: 4px;
            align-self: flex-start;
        }

        .chatbot-bubble--user {
            background: var(--folha);
            color: #fff;
            border-top-right-radius: 4px;
            align-self: flex-end;
            margin-left: auto;
        }

        .chat-loader {
            display: none;
            align-items: center;
            gap: 0.45rem;
            padding: 0 1rem 0.5rem;
            font-size: 0.75rem;
            color: var(--tinta-soft);
        }

        .chat-loader.is-visible { display: flex; }

        .chat-loader-dot {
            width: 7px;
            height: 7px;
            border-radius: 9999px;
            background: var(--folha);
            animation: chatbotPulse 1.2s infinite ease-in-out;
        }

        .chat-loader-dot:nth-child(2) { animation-delay: 0.2s; }
        .chat-loader-dot:nth-child(3) { animation-delay: 0.4s; }

        .chat-panel__form {
            display: flex;
            gap: 0.5rem;
            padding: 0.75rem;
            border-top: 1px solid var(--borda);
            background: #fff;
        }

        .chat-panel__form input {
            flex: 1;
            min-width: 0;
            border: 1px solid var(--borda);
            border-radius: 12px;
            padding: 0.65rem 0.8rem;
            outline: none;
        }

        .chat-panel__form input:focus { border-color: var(--folha); }

        .chat-panel__form button {
            border: none;
            background: var(--folha);
            color: #fff;
            border-radius: 12px;
            padding: 0 1rem;
            font-weight: 600;
            cursor: pointer;
        }

        .offers-empty {
            padding: 2rem 1.25rem;
            text-align: center;
            border: 1px dashed var(--borda);
            border-radius: var(--radius);
            background: rgba(255, 255, 255, 0.55);
            color: var(--tinta-soft);
        }

        @keyframes rise {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: none; }
        }

        @keyframes chatbotPulse {
            0%, 100% { opacity: 0.25; transform: translateY(0); }
            50% { opacity: 1; transform: translateY(-3px); }
        }
    </style>
</head>
<body>
    <header class="hero">
        <div class="hero__media" aria-hidden="true"></div>
        <div class="hero__veil" aria-hidden="true"></div>
        <div class="wrap hero__content">
            <h1 class="hero__brand">{{ $company['name'] }}</h1>
            <p class="hero__lead">Pedidos pelo chat · retirada ou entrega. Confira as ofertas e peça sem cadastro.</p>
            <div class="hero__actions">
                <button type="button" class="btn btn--primary" id="hero-chat">Pedir no chat</button>
                <a href="#ofertas" class="btn btn--ghost">Ver ofertas</a>
            </div>
        </div>
    </header>

    <main>
        <section class="section" id="ofertas">
            <div class="wrap">
                <div class="section__head">
                    <h2 class="section__title">Ofertas do dia</h2>
                    <span class="section__hint">Toque para pedir</span>
                </div>

                @if ($promotionalProducts->isEmpty())
                    <div class="offers-empty">Nenhuma promoção ativa no momento.</div>
                @else
                    <div class="offers">
                        @foreach ($promotionalProducts as $product)
                            @include('vitrine.partials.product-card', [
                                'product' => $product,
                                'offerStrip' => true,
                            ])
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="section" id="catalogo">
            <div class="wrap">
                <div class="section__head">
                    <h2 class="section__title">Catálogo</h2>
                    <span class="section__hint" id="products-page-indicator">Página 1</span>
                </div>

                <div class="toolbar">
                    <label class="search">
                        <span class="search__icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </span>
                        <span class="sr-only">Buscar produtos</span>
                        <input id="products-search" type="search" placeholder="O que você procura?" autocomplete="off" />
                    </label>

                    <div class="chips" id="category-chips-container" role="group" aria-label="Categorias">
                        <button
                            type="button"
                            class="chip"
                            data-category-chip="all"
                            data-category-name="Todas as categorias"
                            aria-pressed="true"
                        >
                            Todas
                        </button>

                        @foreach ($categories as $category)
                            <button
                                type="button"
                                class="chip"
                                data-category-chip="item"
                                data-category-id="{{ $category->id }}"
                                data-category-name="{{ $category->name }}"
                                aria-pressed="false"
                            >
                                {{ $category->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div id="products-grid" class="grid">
                    @foreach ($products as $product)
                        @include('vitrine.partials.product-card', [
                            'product' => $product,
                            'filterable' => true,
                        ])
                    @endforeach
                </div>

                <div id="products-empty" class="empty hidden">
                    Nenhum produto encontrado para o filtro atual.
                </div>

                <div id="products-pagination" class="pager">
                    <button id="products-prev" type="button" class="page-btn">Anterior</button>
                    <div id="products-pages" style="display:flex;gap:0.4rem"></div>
                    <button id="products-next" type="button" class="page-btn">Próxima</button>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="wrap">
            <strong>{{ $company['name'] }}</strong>
            <p>Telefone: {{ $company['phone'] }}</p>
            <p>{{ $company['address'] }}</p>
            @if (! empty($company['cnpj']) && $company['cnpj'] !== 'CNPJ nao informado')
                <p>CNPJ: {{ $company['cnpj'] }}</p>
            @endif
            <p class="powered">Powered by Vendix</p>
        </div>
    </footer>

    <button type="button" class="chat-fab" id="chatbot-toggle" aria-label="Abrir chat">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M7 9h10M7 13h6M4 5h16v11H8l-4 3V5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>

    <section class="chat-panel" id="chatbot-panel" aria-live="polite">
        <header class="chat-panel__head">
            <div>
                <p class="chat-panel__eyebrow">Assistente da loja</p>
                <p class="chat-panel__title">{{ $company['name'] }}</p>
            </div>
            <button type="button" class="chat-panel__close" id="chatbot-minimize" aria-label="Fechar">✕</button>
        </header>

        <div id="chatbot-messages" class="chat-panel__msgs">
            <div class="chatbot-bubble chatbot-bubble--bot">
                Olá! Diga o que precisa — posso montar o pedido e combinar retirada ou entrega.
            </div>
        </div>

        <div id="chatbot-loader" class="chat-loader">
            <span class="chat-loader-dot"></span>
            <span class="chat-loader-dot"></span>
            <span class="chat-loader-dot"></span>
            <span>Consultando resposta...</span>
        </div>

        <form id="chatbot-form" class="chat-panel__form">
            <label for="chatbot-input" class="sr-only">Mensagem</label>
            <input
                id="chatbot-input"
                type="text"
                maxlength="500"
                placeholder="Ex.: quero 2 arroz"
            />
            <button type="submit">Enviar</button>
        </form>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const PRODUCTS_PER_PAGE = 6;

            const productsGrid = document.getElementById('products-grid');
            const productCards = Array.from(productsGrid.querySelectorAll('[data-product-card="1"]'));
            const productsEmpty = document.getElementById('products-empty');
            const productsPagination = document.getElementById('products-pagination');
            const productsPrev = document.getElementById('products-prev');
            const productsNext = document.getElementById('products-next');
            const productsPages = document.getElementById('products-pages');
            const productsPageIndicator = document.getElementById('products-page-indicator');

            const categoryAllButton = document.querySelector('[data-category-chip="all"]');
            const categoryButtons = Array.from(document.querySelectorAll('[data-category-chip="item"]'));
            const searchInput = document.getElementById('products-search');

            let selectedCategoryId = 'all';
            let productsPage = 1;

            function normalizeText(value) {
                return (value || '').toString().trim().toLowerCase();
            }

            function setCategoryActiveStyles() {
                categoryAllButton.setAttribute('aria-pressed', selectedCategoryId === 'all' ? 'true' : 'false');
                categoryButtons.forEach(function (button) {
                    button.setAttribute(
                        'aria-pressed',
                        button.dataset.categoryId === selectedCategoryId ? 'true' : 'false'
                    );
                });
            }

            function getFilteredProducts() {
                const searchTerm = normalizeText(searchInput.value);

                return productCards.filter(function (card) {
                    const cardCategoryId = card.dataset.categoryId || '0';
                    const cardSearchText = normalizeText(card.dataset.search);
                    const matchesCategory = selectedCategoryId === 'all' || cardCategoryId === selectedCategoryId;
                    const matchesSearch = searchTerm === '' || cardSearchText.includes(searchTerm);

                    return matchesCategory && matchesSearch;
                });
            }

            function renderProductsPagination(totalPages) {
                if (totalPages <= 1) {
                    productsPagination.classList.add('hidden');
                    return;
                }

                productsPagination.classList.remove('hidden');
                productsPrev.disabled = productsPage === 1;
                productsNext.disabled = productsPage === totalPages;

                const start = Math.max(1, productsPage - 2);
                const end = Math.min(totalPages, productsPage + 2);
                productsPages.innerHTML = '';

                for (let page = start; page <= end; page += 1) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'page-btn' + (page === productsPage ? ' is-active' : '');
                    button.textContent = String(page);
                    button.addEventListener('click', function () {
                        productsPage = page;
                        renderProducts();
                    });
                    productsPages.appendChild(button);
                }
            }

            function renderProducts() {
                const filteredProducts = getFilteredProducts();
                const totalPages = Math.max(1, Math.ceil(filteredProducts.length / PRODUCTS_PER_PAGE));

                if (productsPage > totalPages) {
                    productsPage = totalPages;
                }

                const start = (productsPage - 1) * PRODUCTS_PER_PAGE;
                const end = start + PRODUCTS_PER_PAGE;
                const currentSlice = filteredProducts.slice(start, end);

                productCards.forEach(function (card) {
                    card.classList.add('hidden');
                });

                currentSlice.forEach(function (card) {
                    card.classList.remove('hidden');
                });

                const hasResults = filteredProducts.length > 0;
                productsEmpty.classList.toggle('hidden', hasResults);
                productsGrid.classList.toggle('hidden', !hasResults);
                productsPageIndicator.textContent = hasResults
                    ? 'Página ' + productsPage + ' de ' + totalPages
                    : 'Nenhum resultado';

                renderProductsPagination(totalPages);
            }

            categoryAllButton.addEventListener('click', function () {
                selectedCategoryId = 'all';
                productsPage = 1;
                setCategoryActiveStyles();
                renderProducts();
            });

            categoryButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    selectedCategoryId = button.dataset.categoryId;
                    productsPage = 1;
                    setCategoryActiveStyles();
                    renderProducts();
                });
            });

            searchInput.addEventListener('input', function () {
                productsPage = 1;
                renderProducts();
            });

            productsPrev.addEventListener('click', function () {
                if (productsPage > 1) {
                    productsPage -= 1;
                    renderProducts();
                }
            });

            productsNext.addEventListener('click', function () {
                const filteredProducts = getFilteredProducts();
                const totalPages = Math.max(1, Math.ceil(filteredProducts.length / PRODUCTS_PER_PAGE));

                if (productsPage < totalPages) {
                    productsPage += 1;
                    renderProducts();
                }
            });

            setCategoryActiveStyles();
            renderProducts();

            const panel = document.getElementById('chatbot-panel');
            const toggleButton = document.getElementById('chatbot-toggle');
            const minimizeButton = document.getElementById('chatbot-minimize');
            const heroChat = document.getElementById('hero-chat');
            const form = document.getElementById('chatbot-form');
            const input = document.getElementById('chatbot-input');
            const messages = document.getElementById('chatbot-messages');
            const loader = document.getElementById('chatbot-loader');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            let chatIsOpen = false;

            const toggleChat = function (openState, prefill) {
                chatIsOpen = openState;
                panel.classList.toggle('is-open', chatIsOpen);

                if (chatIsOpen) {
                    if (typeof prefill === 'string' && prefill !== '') {
                        input.value = prefill;
                    }
                    input.focus();
                }
            };

            const askProduct = function (name) {
                toggleChat(true, 'quero ' + name);
            };

            document.querySelectorAll('[data-product-name]').forEach(function (el) {
                el.addEventListener('click', function () {
                    askProduct(el.dataset.productName || '');
                });
            });

            const appendMessage = function (role, text) {
                const bubble = document.createElement('div');
                bubble.className = role === 'user'
                    ? 'chatbot-bubble chatbot-bubble--user'
                    : 'chatbot-bubble chatbot-bubble--bot';
                bubble.textContent = text;
                messages.appendChild(bubble);
                messages.scrollTop = messages.scrollHeight;
            };

            const setLoading = function (loading) {
                loader.classList.toggle('is-visible', loading);
            };

            toggleButton.addEventListener('click', function () {
                toggleChat(!chatIsOpen);
            });

            minimizeButton.addEventListener('click', function () {
                toggleChat(false);
            });

            heroChat.addEventListener('click', function () {
                toggleChat(true);
            });

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                const message = input.value.trim();

                if (!message) {
                    return;
                }

                appendMessage('user', message);
                input.value = '';
                setLoading(true);

                try {
                    const response = await fetch('{{ route('chatbot.message') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || '',
                        },
                        body: JSON.stringify({ message: message }),
                    });

                    if (!response.ok) {
                        throw new Error('Falha ao enviar mensagem.');
                    }

                    const payload = await response.json();
                    appendMessage('assistant', payload.reply || 'Estamos verificando as promoções do dia...');
                } catch (error) {
                    appendMessage('assistant', 'Não consegui responder agora. Tente novamente em alguns segundos.');
                } finally {
                    setLoading(false);
                }
            });
        });
    </script>
</body>
</html>
