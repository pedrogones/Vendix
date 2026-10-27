@php
    $saleItems = $sale?->items ?? collect();
    $itemsCount = $saleItems->sum('quantity');
    $discountTotal = $saleItems->sum('discount');
    $subtotal = $saleItems->sum(fn ($item) => $item->unit_price * $item->quantity);
@endphp

<div class="pdv">
    <div class="pdv-header">
        <div class="pdv-sale-id">
            <span class="pdv-status-dot" aria-hidden="true"></span>
            <strong>Venda #{{ str_pad((string) ($sale?->id ?? 0), 4, '0', STR_PAD_LEFT) }}</strong>
            <span class="pdv-draft">Em edição</span>
        </div>
        <span class="pdv-operator" title="{{ auth()->user()?->name }}">{{ auth()->user()?->name }}</span>
    </div>

    <div class="pdv-workspace">
        <div class="pdv-main">
            <section class="pdv-entry" aria-labelledby="pdv-entry-title">
                <header class="pdv-section-heading">
                    <h2 id="pdv-entry-title">Adicionar produto</h2>
                </header>

                {{ $this->saleForm }}

            </section>

            <section class="pdv-cart" aria-labelledby="pdv-cart-title">
                <header class="pdv-section-heading pdv-cart-heading">
                    <h2 id="pdv-cart-title">Itens da venda</h2>
                    <span>{{ $itemsCount }} {{ $itemsCount === 1 ? 'unidade' : 'unidades' }}</span>
                </header>

                @if ($saleItems->isNotEmpty())
                    <table class="pdv-items">
                        <caption class="pdv-sr-only">Produtos adicionados à venda</caption>
                        <thead>
                            <tr>
                                <th scope="col">Produto</th>
                                <th scope="col" class="pdv-quantity">Qtd.</th>
                                <th scope="col" class="pdv-unit">Unitário</th>
                                <th scope="col" class="pdv-amount">Total</th>
                                <th scope="col"><span class="pdv-sr-only">Remover</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($saleItems as $item)
                                <tr wire:key="pdv-item-{{ $item->id }}">
                                    <td class="pdv-product-cell">
                                        <span class="pdv-product-name">{{ $item->product?->name ?? 'Produto indisponível' }}</span>
                                        @if ($item->product?->barcode)
                                            <span class="pdv-product-code">{{ $item->product->barcode }}</span>
                                        @endif
                                        @if ($item->discount > 0)
                                            <span class="pdv-item-discount">Desconto de R$ {{ number_format($item->discount, 2, ',', '.') }}</span>
                                        @endif
                                    </td>
                                    <td class="pdv-quantity"><span class="pdv-mobile-label">Qtd. </span>{{ $item->quantity }}</td>
                                    <td class="pdv-unit">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                    <td class="pdv-amount">R$ {{ number_format($item->total_price, 2, ',', '.') }}</td>
                                    <td class="pdv-remove">
                                        <x-filament::icon-button
                                            icon="heroicon-m-x-mark"
                                            color="gray"
                                            size="sm"
                                            :label="'Remover '.$item->product?->name"
                                            wire:click="removeItem({{ $item->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="addItem,confirmSale,removeItem"
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="pdv-empty">
                        <x-filament::icon icon="heroicon-o-shopping-cart" class="pdv-empty-icon" />
                        <p>Nenhum produto adicionado</p>
                        <span>Busque um produto acima para começar a venda.</span>
                    </div>
                @endif
            </section>
        </div>

        <aside class="pdv-checkout" aria-label="Resumo e confirmação da venda">
            <div class="pdv-checkout-details">
                <h2>Resumo da venda</h2>
                <dl class="pdv-breakdown">
                    <div><dt>Subtotal</dt><dd>R$ {{ number_format($subtotal, 2, ',', '.') }}</dd></div>
                    <div><dt>Descontos</dt><dd>{{ $discountTotal > 0 ? '− ' : '' }}R$ {{ number_format($discountTotal, 2, ',', '.') }}</dd></div>
                    <div><dt>Quantidade</dt><dd>{{ $itemsCount }} {{ $itemsCount === 1 ? 'unidade' : 'unidades' }}</dd></div>
                </dl>
                @if ($sale?->client?->cpf)
                    <p class="pdv-client">Cliente identificado por CPF</p>
                @endif
            </div>
            <div class="pdv-checkout-action">
                <div class="pdv-grand-total" aria-live="polite" aria-atomic="true">
                    <span>Total<span class="pdv-mobile-count"> · {{ $itemsCount }} un.</span></span>
                    <strong>R$ {{ number_format($sale?->total ?? 0, 2, ',', '.') }}</strong>
                </div>

                <div class="pdv-payments" wire:key="pdv-payments">
                    <header class="pdv-payments-heading">
                        <h3>Recebimento</h3>
                        <button type="button" class="pdv-payment-add" wire:click="addPaymentLine" @disabled($saleItems->isEmpty())>
                            Dividir
                        </button>
                    </header>
                    @foreach ($this->paymentLines as $index => $line)
                        <div class="pdv-payment-line" wire:key="payment-line-{{ $index }}">
                            <label class="pdv-payment-field">
                                <span>Meio</span>
                                <select wire:model.live="paymentLines.{{ $index }}.method">
                                    @foreach ($this->paymentMethods as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="pdv-payment-field">
                                <span>Valor</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    inputmode="decimal"
                                    wire:model.live="paymentLines.{{ $index }}.amount"
                                    placeholder="0,00"
                                >
                            </label>
                            @if (($line['method'] ?? '') === 'cash')
                                <label class="pdv-payment-field">
                                    <span>Recebido</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        inputmode="decimal"
                                        wire:model.live="paymentLines.{{ $index }}.amount_tendered"
                                        placeholder="0,00"
                                    >
                                </label>
                            @endif
                            @if (count($this->paymentLines) > 1)
                                <button
                                    type="button"
                                    class="pdv-payment-remove"
                                    wire:click="removePaymentLine({{ $index }})"
                                    aria-label="Remover forma de pagamento"
                                >
                                    ×
                                </button>
                            @endif
                        </div>
                    @endforeach
                    <dl class="pdv-payment-summary">
                        <div>
                            <dt>Alocado</dt>
                            <dd>R$ {{ number_format($this->paymentAllocated, 2, ',', '.') }}</dd>
                        </div>
                        @if ($this->paymentChange > 0)
                            <div>
                                <dt>Troco</dt>
                                <dd>R$ {{ number_format($this->paymentChange, 2, ',', '.') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <x-filament::button
                    wire:click="confirmSale"
                    wire:loading.attr="disabled"
                    wire:target="addItem,confirmSale,removeItem"
                    :disabled="$saleItems->isEmpty()"
                    icon="heroicon-m-check"
                    class="pdv-confirm-button"
                >
                    <span wire:loading.remove wire:target="confirmSale">Confirmar e receber</span>
                    <span wire:loading wire:target="confirmSale">Confirmando…</span>
                </x-filament::button>
                <p class="pdv-confirm-note">Registra a venda, o recebimento e a baixa de estoque.</p>
            </div>
        </aside>
    </div>
</div>
