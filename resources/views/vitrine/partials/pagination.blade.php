@if ($paginator->hasPages())
    @php
        $baseQuery = request()->except($pageName);
        $start = max(1, $paginator->currentPage() - 2);
        $end = min($paginator->lastPage(), $paginator->currentPage() + 2);
    @endphp

    <nav class="mt-6 flex flex-wrap items-center justify-center gap-2" aria-label="Paginacao">
        @php
            $previousParams = array_merge($baseQuery, [$pageName => max(1, $paginator->currentPage() - 1)]);
        @endphp
        <a
            href="{{ $paginator->onFirstPage() ? '#' : route('vitrine.preview', $previousParams) }}"
            class="inline-flex h-9 items-center rounded-lg border border-[#5d6370] bg-[#31363d] px-3 text-sm font-semibold text-slate-200 transition {{ $paginator->onFirstPage() ? 'pointer-events-none opacity-40' : 'hover:border-violet-400 hover:text-violet-300' }}"
        >
            Anterior
        </a>

        @for ($page = $start; $page <= $end; $page++)
            @php
                $params = array_merge($baseQuery, [$pageName => $page]);
                $isCurrent = $page === $paginator->currentPage();
            @endphp
            <a
                href="{{ route('vitrine.preview', $params) }}"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border text-sm font-bold transition {{ $isCurrent ? 'border-violet-400 bg-violet-600 text-white' : 'border-[#5d6370] bg-[#31363d] text-slate-200 hover:border-violet-400 hover:text-violet-300' }}"
                aria-current="{{ $isCurrent ? 'page' : 'false' }}"
            >
                {{ $page }}
            </a>
        @endfor

        @php
            $nextParams = array_merge($baseQuery, [$pageName => min($paginator->lastPage(), $paginator->currentPage() + 1)]);
        @endphp
        <a
            href="{{ $paginator->hasMorePages() ? route('vitrine.preview', $nextParams) : '#' }}"
            class="inline-flex h-9 items-center rounded-lg border border-[#5d6370] bg-[#31363d] px-3 text-sm font-semibold text-slate-200 transition {{ $paginator->hasMorePages() ? 'hover:border-violet-400 hover:text-violet-300' : 'pointer-events-none opacity-40' }}"
        >
            Proxima
        </a>
    </nav>
@endif
