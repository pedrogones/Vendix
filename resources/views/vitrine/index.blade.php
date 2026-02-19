<!doctype html>
<html lang="pt-BR" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Vitrine | {{ $company['name'] }}</title>
    <meta name="description" content="Vitrine publica da {{ $company['name'] }} com ofertas e produtos atualizados." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            color-scheme: light;
            --brand-purple: #6d28d9;
            --brand-purple-soft: #f4ecff;
            --brand-yellow: #facc15;
            --brand-yellow-soft: #fef9c3;
        }

        body {
            font-family: 'Manrope', sans-serif;
            background: #f8f4ff;
        }

        .vitrine-background {
            background:
                radial-gradient(circle at 12% 0%, rgba(109, 40, 217, 0.12), transparent 36%),
                radial-gradient(circle at 92% 6%, rgba(250, 204, 21, 0.15), transparent 34%),
                #f8f4ff;
        }

        .category-chip {
            display: inline-flex;
            width: auto;
            align-items: center;
            justify-content: center;
            border: 0;
            border-bottom: 2px solid transparent;
            border-radius: 0;
            background: transparent;
            color: #6b7280;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 6px 2px;
            white-space: nowrap;
            transition: color 0.22s ease, border-color 0.22s ease;
        }

        .category-chip:hover {
            color: #4c1d95;
            border-color: #8b5cf6;
        }

        .category-chip[data-active="true"] {
            color: #4c1d95;
            border-color: var(--brand-purple);
        }

        .page-btn {
            display: inline-flex;
            height: 36px;
            min-width: 36px;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: 1px solid #d6bcfa;
            background: #ffffff;
            color: #6d28d9;
            font-size: 0.86rem;
            font-weight: 700;
            padding: 0 12px;
            transition: all 0.2s ease;
        }

        .page-btn:hover:not(:disabled) {
            border-color: #8b5cf6;
            background: #f5efff;
        }

        .page-btn.is-active {
            border-color: var(--brand-purple);
            background: var(--brand-purple);
            color: #ffffff;
        }

        .page-btn:disabled {
            cursor: not-allowed;
            opacity: 0.4;
        }

        .chatbot-enter {
            opacity: 0;
            transform: translateY(16px) scale(0.98);
            pointer-events: none;
        }

        .chatbot-enter.chatbot-open {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .chatbot-loader-dot {
            width: 7px;
            height: 7px;
            border-radius: 9999px;
            background: var(--brand-purple);
            animation: chatbotPulse 1.2s infinite ease-in-out;
        }

        .chatbot-loader-dot:nth-child(2) { animation-delay: 0.2s; }
        .chatbot-loader-dot:nth-child(3) { animation-delay: 0.4s; }

        @keyframes chatbotPulse {
            0%, 100% { opacity: 0.25; transform: translateY(0); }
            50% { opacity: 1; transform: translateY(-3px); }
        }
    </style>
</head>
<body class="min-h-full text-slate-900">
    <header class="sticky top-0 z-40 border-b border-violet-100 bg-white/95 backdrop-blur">
        <div class="border-b border-violet-100 bg-violet-50/50">
            <div class="mx-auto flex max-w-7xl items-center gap-x-4 gap-y-1 px-4 py-1.5 text-[11px] font-semibold text-slate-600 sm:flex-wrap sm:gap-x-5 sm:gap-y-2 sm:py-2 sm:text-xs sm:px-6 lg:px-8">
                <span class="max-w-[165px] truncate font-bold text-violet-700 sm:max-w-none">{{ $company['name'] }}</span>
                <span class="hidden sm:inline">CNPJ: {{ $company['cnpj'] }}</span>
                <span>Telefone: {{ $company['phone'] }}</span>
                <span class="hidden max-w-[280px] truncate md:inline lg:max-w-none">Endereco: {{ $company['address'] }}</span>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-2.5 sm:px-6 sm:py-4 lg:px-8">
            <div class="grid grid-cols-2 gap-2 sm:gap-3 lg:grid-cols-[300px_minmax(0,1fr)_220px] lg:items-center">
                <button type="button" class="order-1 inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-violet-700 px-3 text-sm font-extrabold uppercase tracking-[0.06em] text-white shadow-md shadow-violet-300/40 transition hover:bg-violet-800 sm:h-12 sm:px-5 sm:text-[15px] lg:h-14 lg:gap-3 lg:px-6 lg:text-base lg:tracking-[0.08em] lg:shadow-lg lg:shadow-violet-300/50">
                    <span class="text-lg lg:text-xl">&#9776;</span>
                    Departamentos
                </button>
                <a href="#todos-os-produtos" class="order-2 inline-flex h-11 items-center justify-center rounded-xl bg-yellow-300 px-3 text-sm font-extrabold uppercase tracking-[0.06em] text-violet-900 shadow-md shadow-yellow-300/40 transition hover:bg-yellow-200 sm:h-12 sm:px-5 sm:text-[15px] lg:order-3 lg:h-14 lg:text-base lg:tracking-[0.08em] lg:shadow-lg lg:shadow-yellow-300/50">
                    Catalogo
                    <span id="catalog-count" class="ml-2 rounded-md bg-violet-900/15 px-1.5 py-0.5 text-[11px] font-bold sm:px-2 sm:text-xs">{{ $products->count() }}</span>
                </a>
                <label class="relative order-3 col-span-2 block lg:order-2 lg:col-span-1">
                    <span class="sr-only">Pesquisar produtos</span>
                    <input
                        id="products-search"
                        type="text"
                        placeholder="Digite o que voce procura..."
                        class="h-11 w-full rounded-xl border border-violet-200 bg-white px-4 pr-11 text-sm text-slate-800 placeholder:text-slate-400 outline-none transition focus:border-violet-400 sm:h-12 sm:px-5 sm:pr-12 sm:text-base lg:h-14 lg:pr-14"
                    />
                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-lg text-violet-500 sm:right-4">&#128269;</span>
                </label>
            </div>
        </div>
    </header>

    <main class="vitrine-background pb-24">
        <section class="mx-auto max-w-7xl px-4 pt-5 sm:px-6 sm:pt-6 lg:px-8">
            <div class="rounded-2xl border border-violet-200 bg-white p-3.5 shadow-[0_10px_20px_rgba(109,40,217,0.08)] sm:p-4">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <h2 class="text-base font-extrabold uppercase tracking-[0.08em] text-violet-900 sm:text-lg">Categorias</h2>
                    <span id="active-category-label" class="text-[10px] font-bold uppercase tracking-[0.08em] text-violet-500 sm:text-[11px]">
                        Todas as categorias
                    </span>
                </div>

                <div id="category-chips-container" class="flex items-center gap-4 overflow-x-auto border-b border-violet-200/90 pb-1.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <button
                        type="button"
                        data-category-chip="all"
                        data-category-name="Todas as categorias"
                        class="category-chip"
                        data-active="true"
                    >
                        <span>Todas</span>
                    </button>

                    @foreach ($categories as $category)
                        <button
                            type="button"
                            data-category-chip="item"
                            data-category-id="{{ $category->id }}"
                            data-category-name="{{ $category->name }}"
                            class="category-chip"
                            data-active="false"
                        >
                            <span class="truncate">{{ $category->name }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="todos-os-produtos" class="mx-auto max-w-7xl px-4 pt-7 sm:px-6 sm:pt-8 lg:px-8">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-extrabold uppercase tracking-[0.05em] text-violet-900 sm:text-2xl">Todos os produtos</h2>
                <span id="products-page-indicator" class="text-sm font-semibold text-violet-600">Pagina 1</span>
            </div>

            <div id="products-grid" class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $product)
                    @include('vitrine.partials.product-card', [
                        'product' => $product,
                        'promotionSection' => false,
                        'filterable' => true,
                    ])
                @endforeach
            </div>

            <div id="products-empty" class="mt-4 hidden rounded-2xl border border-dashed border-violet-200 bg-white px-6 py-12 text-center text-slate-500">
                Nenhum produto encontrado para o filtro atual.
            </div>

            <div id="products-pagination" class="mt-6 flex items-center justify-center gap-2">
                <button id="products-prev" type="button" class="page-btn">Anterior</button>
                <div id="products-pages" class="flex items-center gap-2"></div>
                <button id="products-next" type="button" class="page-btn">Proxima</button>
            </div>
        </section>
        <section class="mx-auto max-w-7xl px-4 pt-5 sm:px-6 sm:pt-6 lg:px-8">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <h2 class="flex items-center gap-2 text-xl font-extrabold uppercase tracking-[0.05em] text-violet-900 sm:text-2xl">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-yellow-300 text-sm text-violet-900">%</span>
                    Ofertas do dia
                </h2>
                <span class="rounded-full border border-yellow-200 bg-yellow-50 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.1em] text-yellow-700">Atualizado automaticamente</span>
            </div>

            @if ($promotionalProducts->isEmpty())
                <div class="rounded-2xl border border-dashed border-violet-200 bg-white px-6 py-12 text-center text-slate-500">
                    Nenhuma promocao ativa no momento.
                </div>
            @else
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($promotionalProducts as $product)
                        @include('vitrine.partials.product-card', ['product' => $product, 'promotionSection' => true])
                    @endforeach
                </div>
            @endif
        </section>

    </main>

    <button
        type="button"
        id="chatbot-toggle"
        class="fixed bottom-6 right-6 z-50 inline-flex h-14 w-14 items-center justify-center rounded-full bg-violet-700 text-white shadow-2xl shadow-violet-300/50 transition hover:bg-violet-800 focus:outline-none focus:ring-4 focus:ring-violet-200"
        aria-label="Abrir chatbot"
    >
        <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M7 9h10M7 13h6M4 5h16v11H8l-4 3V5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </button>

    <section
        id="chatbot-panel"
        class="chatbot-enter fixed bottom-24 right-6 z-50 flex w-[calc(100vw-3rem)] max-w-sm flex-col overflow-hidden rounded-3xl border border-violet-200 bg-white shadow-2xl transition duration-300"
        aria-live="polite"
    >
        <header class="flex items-center justify-between bg-violet-700 px-4 py-3 text-white">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet-100">Assistente IA</p>
                <p class="text-base font-bold">Vitrine {{ $company['name'] }}</p>
            </div>
            <button
                type="button"
                id="chatbot-minimize"
                class="rounded-full bg-white/20 p-1.5 transition hover:bg-white/30"
                aria-label="Minimizar chatbot"
            >
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6 12h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </header>

        <div id="chatbot-messages" class="h-80 space-y-3 overflow-y-auto bg-violet-50 px-4 py-4">
            <div class="max-w-[85%] rounded-2xl rounded-tl-md bg-white px-3 py-2 text-sm text-slate-700 shadow-sm">
                Ola! Posso ajudar com promocoes, estoque e duvidas sobre produtos.
            </div>
        </div>

        <div id="chatbot-loader" class="hidden items-center gap-2 px-4 pb-2">
            <span class="chatbot-loader-dot"></span>
            <span class="chatbot-loader-dot"></span>
            <span class="chatbot-loader-dot"></span>
            <span class="text-xs font-medium text-slate-500">Consultando resposta...</span>
        </div>

        <form id="chatbot-form" class="border-t border-violet-100 bg-white p-3">
            <label for="chatbot-input" class="sr-only">Mensagem</label>
            <div class="flex items-center gap-2">
                <input
                    id="chatbot-input"
                    type="text"
                    maxlength="500"
                    placeholder="Digite sua mensagem..."
                    class="h-11 w-full rounded-xl border border-violet-200 px-3 text-sm text-slate-900 outline-none transition focus:border-violet-500"
                />
                <button
                    type="submit"
                    class="inline-flex h-11 shrink-0 items-center justify-center rounded-xl bg-violet-700 px-4 text-sm font-bold text-white transition hover:bg-violet-800"
                >
                    Enviar
                </button>
            </div>
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
            const activeCategoryLabel = document.getElementById('active-category-label');

            const searchInput = document.getElementById('products-search');
            const catalogCount = document.getElementById('catalog-count');

            let selectedCategoryId = 'all';
            let selectedCategoryName = 'Todas as categorias';
            let productsPage = 1;

            function normalizeText(value) {
                return (value || '').toString().trim().toLowerCase();
            }

            function setCategoryActiveStyles() {
                categoryAllButton.dataset.active = selectedCategoryId === 'all' ? 'true' : 'false';
                categoryButtons.forEach(function (button) {
                    button.dataset.active = button.dataset.categoryId === selectedCategoryId ? 'true' : 'false';
                });

                activeCategoryLabel.textContent = selectedCategoryName;
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
                    ? 'Pagina ' + productsPage + ' de ' + totalPages
                    : 'Nenhum resultado';

                catalogCount.textContent = String(filteredProducts.length);
                renderProductsPagination(totalPages);
            }

            categoryAllButton.addEventListener('click', function () {
                selectedCategoryId = 'all';
                selectedCategoryName = categoryAllButton.dataset.categoryName || 'Todas as categorias';
                productsPage = 1;
                setCategoryActiveStyles();
                renderProducts();
            });

            categoryButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    selectedCategoryId = button.dataset.categoryId;
                    selectedCategoryName = button.dataset.categoryName || 'Categoria';
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
            const form = document.getElementById('chatbot-form');
            const input = document.getElementById('chatbot-input');
            const messages = document.getElementById('chatbot-messages');
            const loader = document.getElementById('chatbot-loader');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            let chatIsOpen = false;

            const toggleChat = function (openState) {
                chatIsOpen = openState;
                panel.classList.toggle('chatbot-open', chatIsOpen);

                if (chatIsOpen) {
                    input.focus();
                }
            };

            const appendMessage = function (role, text) {
                const bubble = document.createElement('div');
                bubble.className = role === 'user'
                    ? 'ml-auto max-w-[85%] rounded-2xl rounded-tr-md bg-violet-700 px-3 py-2 text-sm text-white shadow-sm'
                    : 'max-w-[85%] rounded-2xl rounded-tl-md bg-white px-3 py-2 text-sm text-slate-700 shadow-sm';

                bubble.textContent = text;
                messages.appendChild(bubble);
                messages.scrollTop = messages.scrollHeight;
            };

            const setLoading = function (loading) {
                loader.classList.toggle('hidden', !loading);
                loader.classList.toggle('flex', loading);
            };

            toggleButton.addEventListener('click', function () {
                toggleChat(!chatIsOpen);
            });

            minimizeButton.addEventListener('click', function () {
                toggleChat(false);
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
                    appendMessage('assistant', payload.reply || 'Estamos verificando as promocoes do dia...');
                } catch (error) {
                    appendMessage('assistant', 'Nao consegui responder agora. Tente novamente em alguns segundos.');
                } finally {
                    setLoading(false);
                }
            });
        });
    </script>
</body>
</html>
