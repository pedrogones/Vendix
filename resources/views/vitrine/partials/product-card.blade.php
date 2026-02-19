@php
    $hasPromotion = !is_null($product->promotional_price);
    $outOfStock = (int) $product->stock === 0;
    $imageUrl = $product->image?->url ?? getDefaultNoFile();
    $summary = \Illuminate\Support\Str::limit(trim(strip_tags((string) $product->description)), 100);
    $discount = null;

    if ($hasPromotion && (float) $product->price > 0) {
        $discount = (int) round(((float) $product->price - (float) $product->promotional_price) / (float) $product->price * 100);
    }

    $filterable = $filterable ?? false;
    $searchText = \Illuminate\Support\Str::lower($product->name . ' ' . $summary . ' ' . optional($product->category)->name);
@endphp

<article
    @if($filterable)
        data-product-card="1"
        data-category-id="{{ (int) ($product->category_id ?? 0) }}"
        data-search="{{ $searchText }}"
    @endif
    class="group overflow-hidden rounded-2xl border border-[#e4d8ff] bg-white shadow-[0_10px_26px_rgba(109,40,217,0.10)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_18px_34px_rgba(109,40,217,0.18)] {{ $promotionSection ? 'ring-2 ring-yellow-200' : '' }}"
>
    <div class="relative aspect-[4/3] overflow-hidden bg-[#f8f4ff]">
        <img
            src="{{ $imageUrl }}"
            alt="{{ $product->name }}"
            class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
            loading="lazy"
        />

        <div class="absolute left-3 top-3 flex flex-wrap gap-2">
            @if ($hasPromotion)
                <span class="rounded-md bg-yellow-300 px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-[0.08em] text-violet-900">
                    {{ $discount !== null ? $discount . '% OFF' : 'Promocao' }}
                </span>
            @endif

            @if ($outOfStock)
                <span class="rounded-md bg-slate-900 px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-[0.08em] text-white">
                    Sem estoque
                </span>
            @endif
        </div>
    </div>

    <div class="space-y-3 p-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.08em] text-violet-600">
                {{ $product->category?->name ?? 'Sem categoria' }}
            </p>
            <h3 class="mt-1 text-[1.05rem] font-bold leading-snug text-slate-900">{{ $product->name }}</h3>
            <p class="mt-1.5 min-h-[2.75rem] text-sm leading-5 text-slate-600">
                {{ $summary !== '' ? $summary : 'Produto sem descricao cadastrada.' }}
            </p>
        </div>

        <div class="space-y-1">
            @if ($hasPromotion)
                <p class="text-sm font-semibold text-slate-400 line-through">
                    de R$ {{ number_format((float) $product->price, 2, ',', '.') }}
                </p>
                <p class="text-3xl font-extrabold leading-none text-violet-700">
                    R$ {{ number_format((float) $product->promotional_price, 2, ',', '.') }}
                </p>
            @else
                <p class="text-3xl font-extrabold leading-none text-violet-900">
                    R$ {{ number_format((float) $product->price, 2, ',', '.') }}
                </p>
            @endif

            <p class="text-xs font-semibold text-slate-500">
                {{ $outOfStock ? 'Produto indisponivel no momento' : 'Estoque: ' . max((int) $product->stock, 0) }}
            </p>
        </div>

        <button
            type="button"
            class="inline-flex w-full items-center justify-center rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm font-semibold text-violet-800 transition hover:border-violet-400 hover:bg-violet-600 hover:text-white"
        >
            Ver detalhes
        </button>
    </div>
</article>
