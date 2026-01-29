<x-filament-panels::page>


    {{-- ETAPA 1 --}}
    @if (! $started)
        <div class="pdv">
            <div class="pdv-wrap max-w-2xl mx-auto mt-10">
                <div class="pdv-hero">
                    <div class="pdv-title">
                        <span class="pdv-eyebrow">Venda rapida</span>
                        <h1>Iniciar venda</h1>
                        <p>Identifique o cliente se desejar e abra um novo atendimento.</p>
                        <div class="pdv-meta">
                            <span class="pdv-pill">Status: pronto</span>
                            <span class="pdv-pill">Operador: {{ auth()->user()->name ?? 'Usuario' }}</span>
                        </div>
                    </div>
                </div>

                <div class="pdv-grid">
                    <div class="space-y-4">
                        <div class="pdv-panel pdv-searchbox space-y-4">
                            <div class="pdv-panel-head">
                                <div class="pdv-panel-title">Dados iniciais</div>
                                <div class="pdv-hint">CPF e opcional</div>
                            </div>

                            {{ $this->startForm }}

                            <x-filament::button
                                size="lg"
                                color="success"
                                wire:click="startSale"
                                wire:loading.attr="disabled"
                                class="w-full"
                            >
                                <span wire:loading.remove>Iniciar venda</span>
                                <span wire:loading>Iniciando...</span>
                            </x-filament::button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="pdv-panel">
                            <div class="pdv-panel-head">
                                <div class="pdv-panel-title">Checklist rapido</div>
                            </div>
                            <div class="pdv-muted-block">
                                Confirme o cliente, escaneie produtos e finalize a venda pelo resumo.
                            </div>
                            <div class="pdv-muted-block" style="margin-top: 10px;">
                                Dica: o codigo de barras pode ser digitado direto na busca.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif


    {{-- ETAPA 2 --}}
    @include('filament.pages.components.form-sales', ['started' => $started])

</x-filament-panels::page>
