<x-filament-panels::page>
    @php
        $kpis = $kpis ?? [];
        $updatedAt = $updated_at ?? '-';
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Itens com estoque baixo</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                {{ $kpis['low'] ?? 0 }}
            </p>
            <p class="mt-1 text-xs text-gray-500">Abaixo do minimo definido</p>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Itens esgotados</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                {{ $kpis['out'] ?? 0 }}
            </p>
            <p class="mt-1 text-xs text-gray-500">Estoque igual a zero</p>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Produtos ativos</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                {{ $kpis['total'] ?? 0 }}
            </p>
            <p class="mt-1 text-xs text-gray-500">Atualizado em {{ $updatedAt }}</p>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
