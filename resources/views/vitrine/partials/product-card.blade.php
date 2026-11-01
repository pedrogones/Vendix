@php
    $hasPromotion = app(\App\Services\Products\ProductPricingService::class)->isPromotion($product);
    $outOfStock = (int) $product->stock === 0;
    $imageUrl = $product->image?->url ?? getDefaultNoFile();
    $discount = null;

    if ($hasPromotion && (float) $product->price > 0) {
        $discount = (int) round(((float) $product->price - (float) $product->promotional_price) / (float) $product->price * 100);
    }

    $filterable = $filterable ?? false;
    $offerStrip = $offerStrip ?? false;
    $searchText = \Illuminate\Support\Str::lower(
        $product->name.' '.(string) optional($product->category)->name
    );
@endphp

<button
    type="button"
    @if($filterable)
        data-product-card="1"
        data-category-id="{{ (int) ($product->category_id ?? 0) }}"
        data-search="{{ $searchText }}"
    @endif
    data-product-name="{{ $product->name }}"
    class="{{ $offerStrip ? 'offer-card' : 'product' }}"
>
    <div class="{{ $offerStrip ? 'offer-card__img' : 'product__img' }}">
        <img
            src="{{ $imageUrl }}"
            alt="{{ $product->name }}"
            loading="lazy"
        />

        @if ($hasPromotion && $discount !== null)
            <span class="badge-off">{{ $discount }}% OFF</span>
        @elseif ($hasPromotion)
            <span class="badge-off">Oferta</span>
        @endif

        @if ($outOfStock && ! $offerStrip)
            <span class="badge-out">Indisponível</span>
        @endif
    </div>

    <div class="{{ $offerStrip ? 'offer-card__body' : 'product__body' }}">
        @unless ($offerStrip)
            <p class="product__cat">{{ $product->category?->name ?? 'Sem categoria' }}</p>
        @endunless

        <{{ $offerStrip ? 'p' : 'h3' }} class="{{ $offerStrip ? 'offer-card__name' : 'product__name' }}">
            {{ $product->name }}
        </{{ $offerStrip ? 'p' : 'h3' }}>

        @if ($hasPromotion)
            <p class="price-old">de R$ {{ number_format((float) $product->price, 2, ',', '.') }}</p>
            <p class="price-now">R$ {{ number_format((float) $product->promotional_price, 2, ',', '.') }}</p>
        @else
            <p class="price-now">R$ {{ number_format((float) $product->price, 2, ',', '.') }}</p>
        @endif

        @unless ($offerStrip)
            <span class="product__ask">Pedir no chat →</span>
        @endunless
    </div>
</button>
