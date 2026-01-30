@php
    $itemsCount = $sale?->items->sum('quantity') ?? 0;
    $discountTotal = $sale?->items->sum('discount') ?? 0;
    $subtotal = $sale?->items->sum(fn ($item) => $item->unit_price * $item->quantity) ?? 0;
    $clientCpf = $sale?->client?->cpf ?? 'Nao informado';
    $operatorName = auth()->user()->name ?? 'Usuario';
@endphp

<div class="pdv">
    <div class="pdv-wrap mt-6">

        {{-- Topo --}}
        <div class="pdv-top">
            <div class="pdv-title min-w-0">
                <span class="pdv-eyebrow">Venda em andamento</span>
                <h1>Caixa aberto</h1>
                <p>Busque por nome, codigo de barras ou QR.</p>
                <div class="pdv-meta">
                    <span class="pdv-pill">Venda #{{ $sale?->id ?? '-' }}</span>
{{--                    <span class="pdv-pill">Cliente: {{ $clientCpf }}</span>--}}
                    <span class="pdv-pill">Operador: {{ $operatorName }}</span>
                </div>
            </div>

            <div class="pdv-kpis min-w-0">
                <div class="pdv-kpi">
                    <div class="lbl">Itens</div>
                    <div class="val">{{ $itemsCount }}</div>
                </div>
                <div class="pdv-kpi">
                    <div class="lbl">Desconto</div>
                    <div class="val">R$ {{ number_format($discountTotal, 2, ',', '.') }}</div>
                </div>
                <div class="pdv-kpi">
                    <div class="lbl">Total</div>
                    <div class="val">R$ {{ number_format($sale?->total ?? 0, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>

        {{-- Corpo --}}
        <div class="pdv-grid min-w-0">

            {{-- Esquerda --}}
            <div class="space-y-4 min-w-0">

                {{-- Adicionar item --}}
                <div class="min-w-0">
                    <div class="">
                        <div class="">Adicionar item</div>
                        <div class="">Busque e confirme o produto</div>
                    </div>

                    {{ $this->saleForm }}

                    <div class="pdv-preview min-w-0">
                        @if($this->selectedProduct)
                            <div class="min-w-0">
                                <div class="name truncate">
                                    {{ $this->selectedProduct->name }}
                                </div>

                                <div class="meta">
                                    Unitario: R$ {{ number_format($this->unitPrice, 2, ',', '.') }}
                                    | Estoque: {{ $this->selectedProduct->stock }}
                                </div>
                            </div>

                            <div class="total">
                                <div class="lbl">Subtotal</div>
                                <div class="val">
                                    R$ {{ number_format($this->itemSubtotal, 2, ',', '.') }}
                                </div>
                            </div>
                        @else
                            <div class="text-sm">
                                Selecione um produto para ver a previa.
                            </div>
                        @endif
                    </div>

                    <div class="mt-3">
                        <x-filament::button
                            wire:click="addItem"
                            size="lg"
                            color="success"
                            class="w-full"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove>Adicionar item</span>
                            <span wire:loading>Adicionando...</span>
                        </x-filament::button>
                    </div>
                </div>

                {{-- Tabela de itens --}}
                <div class="pdv-panel min-w-0" style="margin-top:.57rem">
                    <div class="pdv-panel-head">
                        <div class="pdv-panel-title">Itens da venda</div>
                        <div class="pdv-hint">{{ $itemsCount }} itens no carrinho</div>
                    </div>

                    @if ($sale && $sale->items->count())

                        {{-- MOBILE: cards --}}
                        <div class="space-y-3 lg:hidden">
                            @foreach ($sale->items as $item)
                                <div class="pdv-item-card" wire:key="sale-item-card-{{ $item->id }}">
                                    <div class="pdv-item-top">
                                        <div class="pdv-item-name">
                                            {{ $item->product->name }}
                                        </div>

                                        <x-filament::icon-button
                                            icon="heroicon-m-trash"
                                            color="danger"
                                            size="sm"
                                            wire:click="removeItem({{ $item->id }})"
                                            wire:loading.attr="disabled"
                                        />
                                    </div>

                                    <div class="pdv-item-meta">
                                        <span class="pdv-pill">{{ $item->quantity }} un</span>
                                        <span class="pdv-item-muted">
                                            Unitario: R$ {{ number_format($item->unit_price, 2, ',', '.') }}
                                        </span>
                                        <span class="pdv-item-muted">
                                            Desconto: {{ $item->discount ? 'R$ ' . number_format($item->discount, 2, ',', '.') : '-' }}
                                        </span>
                                    </div>

                                    <div class="pdv-item-bottom">
                                        <div class="pdv-item-muted">
                                            {{ $item->product->barcode ?? 'Sem codigo' }}
                                        </div>

                                        <div class="pdv-item-total">
                                            R$ {{ number_format($item->total_price, 2, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- DESKTOP: tabela --}}
                        <div class="hidden lg:block">
                            <div class="pdv-table-wrap">
                                <table class="pdv-table">
                                    <thead>
                                        <tr>
                                            <th>Produto</th>
                                            <th class="text-center">Qtd</th>
                                            <th class="text-right">Unitario</th>
                                            <th class="text-right">Desconto</th>
                                            <th class="text-right">Total</th>
                                            <th class="text-center">Acoes</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($sale->items as $item)
                                            <tr class="pdv-row" wire:key="sale-item-row-{{ $item->id }}">
                                                <td>
                                                    <div class="pdv-prod">{{ $item->product->name }}</div>
                                                    <div class="pdv-sub">{{ $item->product->barcode ?? 'Sem codigo' }}</div>
                                                </td>

                                                <td class="text-center">
                                                    <span class="pdv-pill">{{ $item->quantity }}</span>
                                                </td>

                                                <td class="text-right pdv-money">
                                                    R$ {{ number_format($item->unit_price, 2, ',', '.') }}
                                                </td>

                                                <td class="text-right pdv-money">
                                                    {{ $item->discount ? 'R$ ' . number_format($item->discount, 2, ',', '.') : '-' }}
                                                </td>

                                                <td class="text-right pdv-money">
                                                    R$ {{ number_format($item->total_price, 2, ',', '.') }}
                                                </td>

                                                <td class="text-center">
                                                    <x-filament::icon-button
                                                        icon="heroicon-m-trash"
                                                        color="danger"
                                                        wire:click="removeItem({{ $item->id }})"
                                                        wire:loading.attr="disabled"
                                                    />
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    @else
                        <div class="pdv-muted-block">
                            Nenhum item adicionado ainda. Use a busca acima para incluir produtos.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Direita --}}
            <div class="pdv-sticky space-y-4 min-w-0">
                <div class="pdv-panel min-w-0">
                    <div class="pdv-panel-head">
                        <div class="pdv-panel-title">Resumo do pagamento</div>
                        <div class="pdv-hint">Finalize com seguranca</div>
                    </div>

                    <div class="pdv-summary">
                        <div class="pdv-total">
                            <div class="lbl">Total da venda</div>
                            <div class="val">
                                R$ {{ number_format($sale?->total ?? 0, 2, ',', '.') }}
                            </div>
                        </div>

                        <div class="pdv-muted-block">
                            Subtotal: <strong style="color: var(--text)">R$ {{ number_format($subtotal, 2, ',', '.') }}</strong>
                        </div>

                        <div class="pdv-muted-block">
                            Descontos: <strong style="color: var(--text)">R$ {{ number_format($discountTotal, 2, ',', '.') }}</strong>
                        </div>

                        <div class="pdv-muted-block">
                            Itens: <strong style="color: var(--text)">{{ $itemsCount }}</strong>
                        </div>

                        <div class="pdv-buttons space-y-3">
                            <x-filament::button
                                wire:click="confirmSale"
                                size="lg"
                                color="success"
                                class="w-full"
                                wire:loading.attr="disabled"
                            >
                                Confirmar venda
                            </x-filament::button>
                        </div>

                        <div class="pdv-note pdv-muted-block">
                            Dica: depois voce pode informar forma de pagamento e troco.
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
