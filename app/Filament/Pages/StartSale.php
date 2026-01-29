<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Services\Stocks\ConfirmSaleService;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class StartSale extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedShoppingCart;
    protected static string|null|\BackedEnum $activeNavigationIcon = Heroicon::OutlinedShoppingCart;
    protected static ?string $title = 'Iniciar Venda';
    protected static ?string $navigationLabel = 'Iniciar Venda';
    protected string $view = 'filament.pages.start-sale';
    protected static ?string $slug = 'start-sale/{sale?}';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('create-sales') ?? false;
    }

    public bool $started = false;

    public ?Sale $sale = null;

    public array $startData = [
        'has_cpf' => false,
        'cpf' => null,
    ];

    public array $saleData = [
        'product_id' => null,
        'product_search' => null,
        'quantity' => 1,
        'discount' => 0,
    ];

    public array $productSearchResults = [];

    protected function getForms(): array
    {
        return [
            'startForm',
            'saleForm',
        ];
    }

    public function mount(): void
    {
        $this->startForm->fill($this->startData);
        $this->saleForm->fill($this->saleData);
    }

    protected function startForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('cpf')
                    ->label('CPF para identificação do cliente')
                    ->placeholder('Opcional')
                    ->mask('999.999.999-99')
                    ->helperText('Se informar, facilita histórico de compra e trocas futuras.')
                    ->dehydrateStateUsing(fn (?string $state) => $state ? preg_replace('/\D+/', '', $state) : null),
            ])
            ->statePath('startData');
    }

    public function saleForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('product_search')
                    ->label('Produto')
                    ->placeholder('Digite nome ou código de barras')
                    ->debounce(300)
                    ->live()
                    ->afterStateUpdated(fn ($state) => $this->updatedSaleDataProductSearch($state))
                    ->required(),

                Hidden::make('product_id')
                    ->required(),

                TextInput::make('quantity')
                    ->label('Quantidade')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->live()
                    ->required(),

                TextInput::make('discount')
                    ->label('Desconto no item')
                    ->numeric()
                    ->prefix('R$')
                    ->minValue(0)
                    ->default(0)
                    ->live()
                    ->helperText('Se não tiver desconto, deixe 0.'),
            ])
            ->statePath('saleData');
    }

    public function updatedSaleDataProductSearch(?string $value): void
    {
        $search = trim((string) $value);

        if ($search === '' || mb_strlen($search) < 2) {
            $this->productSearchResults = [];
            return;
        }

        $results = Product::query()
            ->where('name', 'like', "%{$search}%")
            ->orWhere('barcode', 'like', "%{$search}%")
            ->orWhere('sku', 'like', "%{$search}%")
            ->limit(8)
            ->get();

        $this->productSearchResults = $results->map(fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->name,
            'barcode' => $product->barcode,
            'stock' => $product->stock,
            'price' => (float) $product->final_price,
        ])->toArray();
    }

    public function selectProduct(int $productId): void
    {
        $product = Product::find($productId);

        if (! $product || ! $product->is_active) {
            Notification::make()
                ->title('Produto indisponível')
                ->danger()
                ->send();
            return;
        }

        $this->saleData['product_id'] = $product->id;
        $this->saleData['product_search'] = $product->name;
        $this->productSearchResults = [];

        $this->saleForm->fill($this->saleData);
    }

    public function startSale(): void
    {
        $this->startForm->validate();

        $cpf = $this->startData['cpf'] ?? null;

        if (!empty($this->startData['has_cpf']) && !empty($this->startData['cpf'])) {
            $cpf = preg_replace('/\D+/', '', $this->startData['cpf']);
        }

        $clientId = null;

        if ($cpf) {
            $client = Client::firstOrCreate(['cpf' => $cpf]);
            $clientId = $client->id;
        }

        $this->sale = Sale::create([
            'status' => 'draft',
            'user_id' => auth()->id(),
            'client_id' => $clientId,
            'total' => 0,
        ]);

        $this->started = true;

        $this->resetSaleForm();
        $this->sale->load('items.product');
    }

    public function addItem(): void
    {
        if (!$this->sale) {
            return;
        }

        $this->validate([
            'saleData.product_id' => 'required|exists:products,id',
            'saleData.quantity'   => 'required|integer|min:1',
            'saleData.discount'   => 'nullable|numeric|min:0',
        ]);

        $product = Product::find($this->saleData['product_id']);
        $quantity = (int) $this->saleData['quantity'];
        $discount = (float) ($this->saleData['discount'] ?? 0);

        if (! $product || ! $product->is_active) {
            Notification::make()
                ->title('Produto indisponível')
                ->danger()
                ->send();
            return;
        }

        if ($product->stock < $quantity) {
            Notification::make()
                ->title('Estoque insuficiente')
                ->body("Disponível: {$product->stock}")
                ->danger()
                ->send();
            return;
        }

        $unitPrice = (float) $product->final_price;
        $itemTotal = max(0, ($unitPrice * $quantity) - $discount);

        $this->sale->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'discount' => $discount,
            'unit_price' => $unitPrice,
            'total_price' => $itemTotal,
        ]);

        $this->recalculateSaleTotal();

        $this->resetSaleForm();
        $this->sale->refresh()->load('items.product');
    }

    public function removeItem(int $itemId): void
    {
        if (!$this->sale) {
            return;
        }

        $this->sale->items()->whereKey($itemId)->delete();

        $this->recalculateSaleTotal();

        $this->sale->refresh()->load('items.product');
    }

    protected function recalculateSaleTotal(): void
    {
        $total = (float) $this->sale->items()->sum('total_price');
        $this->sale->update(['total' => $total]);
    }

    protected function resetSaleForm(): void
    {
        $this->saleData = [
            'product_id' => null,
            'product_search' => null,
            'quantity' => 1,
            'discount' => 0,
        ];

        $this->productSearchResults = [];
        $this->saleForm->fill($this->saleData);
    }

    public function getSelectedProductProperty(): ?Product
    {
        $id = $this->saleData['product_id'] ?? null;
        return $id ? Product::find($id) : null;
    }

    public function getUnitPriceProperty(): float
    {
        return (float) ($this->selectedProduct?->final_price ?? 0);
    }

    public function getItemSubtotalProperty(): float
    {
        $qty = (int) ($this->saleData['quantity'] ?? 1);
        $discount = (float) ($this->saleData['discount'] ?? 0);
        return max(0, ($this->unitPrice * $qty) - $discount);
    }

    public function confirmSale(): void
    {
        if (! $this->sale) {
            return;
        }

        try {
            app(ConfirmSaleService::class)->confirm($this->sale);

            Notification::make()
                ->title('Venda confirmada com sucesso.')
                ->success()
                ->send();

            $this->reset(['started', 'sale']);
            $this->startData = ['has_cpf' => false, 'cpf' => null];
            $this->saleData = ['product_id' => null, 'product_search' => null, 'quantity' => 1, 'discount' => 0];
            $this->productSearchResults = [];
            $this->startForm->fill($this->startData);
            $this->saleForm->fill($this->saleData);
        } catch (\DomainException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
