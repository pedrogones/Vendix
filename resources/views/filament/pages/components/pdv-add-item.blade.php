@php($selectedProduct = $this->selectedProduct)

<div class="pdv-item-action">
    @if ($selectedProduct)
        <div class="pdv-product-info" aria-live="polite">
            <span>Unitário <strong>R$ {{ number_format($this->unitPrice, 2, ',', '.') }}</strong></span>
            <span>Estoque <strong>{{ $selectedProduct->stock }}</strong></span>
            <span class="pdv-line-total">Este item <strong>R$ {{ number_format($this->itemSubtotal, 2, ',', '.') }}</strong></span>
        </div>
    @endif

    <x-filament::button
        wire:click="addItem"
        wire:loading.attr="disabled"
        wire:target="addItem,confirmSale,removeItem"
        :disabled="! $selectedProduct"
        icon="heroicon-m-plus"
        color="gray"
        class="pdv-add-button"
    >
        <span wire:loading.remove wire:target="addItem">Adicionar à venda</span>
        <span wire:loading wire:target="addItem">Adicionando…</span>
    </x-filament::button>
</div>
