<x-filament-panels::page>
    <x-filament::page>
        <div class="grid grid-cols-3 gap-6">

            {{-- PRODUTOS --}}
            <div class="col-span-2 space-y-4">

                <x-filament::card>
                    <select
                        wire:model="productId"
                        class="w-full rounded-md border border-gray-300 px-3 py-2"
                    >
                        <option value="">Selecione um produto</option>

                        @foreach(\App\Models\Product::orderBy('name')->get(['id','name']) as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>

                @if($productId)
                        @php $product = \App\Models\Product::find($productId); @endphp

                        <div class="grid grid-cols-4 gap-4 mt-4">
                            <x-filament::input
                                label="Preço"
                                :value="$product->final_price"
                                disabled
                            />

                            <x-filament::input
                                type="number"
                                label="Quantidade"
                                wire:model="quantity"
                                min="1"
                            />

                            <x-filament::input
                                label="Total"
                                :value="$product->final_price * $quantity"
                                disabled
                            />

                            <x-filament::button
                                wire:click="addItem"
                                color="success"
                            >
                                Adicionar
                            </x-filament::button>
                        </div>
                    @endif
                </x-filament::card>

                {{-- LISTA --}}
                <x-filament::card>
                    @foreach($sale->items as $item)
                        <div class="flex justify-between items-center border-b py-2">
                            <span>{{ $item->product->name }} ({{ $item->quantity }})</span>
                            <span>R$ {{ number_format($item->total_price, 2, ',', '.') }}</span>
                            <x-filament::button
                                wire:click="removeItem({{ $item->id }})"
                                color="danger"
                                size="sm"
                            >
                                X
                            </x-filament::button>
                        </div>
                    @endforeach
                </x-filament::card>

            </div>

            {{-- RESUMO --}}
            <div class="space-y-4">
                <x-filament::card>
                    <x-filament::input
                        label="Desconto"
                        type="number"
                        wire:model="discount"
                    />

                    <p class="mt-4 font-bold">
                        Total: R$ {{ number_format($sale->total, 2, ',', '.') }}
                    </p>

                    <x-filament::button
                        wire:click="confirmSale"
                        color="success"
                        class="mt-4 w-full"
                    >
                        Confirmar Venda
                    </x-filament::button>
                </x-filament::card>
            </div>

        </div>
    </x-filament::page>

</x-filament-panels::page>
