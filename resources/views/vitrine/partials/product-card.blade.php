@php
    $hasPromotion = !is_null($product->promotional_price);
    $outOfStock = (int) $product->stock === 0;
    $imageUrl = $product->image?->url ?? getDefaultNoFile();
    $summary = \Illuminate\Support\Str::limit(trim(strip_tags((string) $product->description)), 70);
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
    class="group overflow-hidden rounded-xl border border-[#e4d8ff] bg-white shadow-[0_8px_18px_rgba(109,40,217,0.10)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_24px_rgba(109,40,217,0.16)] {{ $promotionSection ? 'ring-1 ring-yellow-200' : '' }}"
>
    <div class="relative aspect-[5/4] overflow-hidden bg-[#f8f4ff]">
        <img
            src="{{ $imageUrl }}"
            alt="{{ $product->name }}"
            class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
            loading="lazy"
        />

        <div class="absolute left-2 top-2 flex flex-wrap gap-1.5">
            @if ($hasPromotion)
                <span class="rounded-md bg-yellow-300 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-violet-900">
                    {{ $discount !== null ? $discount . '% OFF' : 'Promocao' }}
                </span>
            @endif

            @if ($outOfStock)
                <span class="rounded-md bg-slate-900 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-white">
                    Sem estoque
                </span>
            @endif
        </div>
    </div>

    <div class="space-y-2.5 p-2.5 sm:p-3">
        <div>
            <p class="hidden text-[10px] font-semibold uppercase tracking-[0.08em] text-violet-600 sm:block">
                {{ $product->category?->name ?? 'Sem categoria' }}
            </p>
            <h3 class="mt-0.5 text-sm font-bold leading-snug text-slate-900 sm:text-[0.95rem]" title="{{ $product->name }}">
                {{ $product->name }}
            </h3>
            <p class="mt-1 hidden text-xs leading-4 text-slate-600 sm:block">
                {{ $summary !== '' ? $summary : 'Produto sem descricao cadastrada.' }}
            </p>
        </div>

        <div class="space-y-0.5">
            @if ($hasPromotion)
                <p class="text-[11px] font-semibold text-slate-400 line-through">
                    de R$ {{ number_format((float) $product->price, 2, ',', '.') }}
                </p>
                <p class="text-lg font-extrabold leading-none text-violet-700 sm:text-xl">
                    R$ {{ number_format((float) $product->promotional_price, 2, ',', '.') }}
                </p>
            @else
                <p class="text-lg font-extrabold leading-none text-violet-900 sm:text-xl">
                    R$ {{ number_format((float) $product->price, 2, ',', '.') }}
                </p>
            @endif

            <p class="text-[11px] font-semibold text-slate-500">
                {{ $outOfStock ? 'Indisponivel' : 'Estoque: ' . max((int) $product->stock, 0) }}
            </p>
        </div>

        <button
            type="button"
            class="inline-flex w-full items-center justify-center rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-semibold text-violet-800 transition hover:border-violet-400 hover:bg-violet-600 hover:text-white"
        >
            Ver detalhes
        </button>
    </div>
</article>
