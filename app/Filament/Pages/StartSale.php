<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Services\Stocks\ConfirmSaleService;
use Filament\Forms;
use Filament\Forms\Components\Select;
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
    protected static string|null|\UnitEnum $navigationGroup = 'Vendas';
    protected static string|null|\BackedEnum $activeNavigationIcon = Heroicon::OutlinedShoppingCart;
    protected static ?string $title = 'Iniciar Venda';
    protected static ?string $navigationLabel = 'Iniciar Venda';
    protected string $view = 'filament.pages.start-sale';
    protected static ?string $slug = 'start-sale/{sale?}';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('create-sales') ?? false;
    }

    public ?Sale $sale = null;

    public array $saleData = [
        'cpf' => null,
        'product_id' => null,
        'quantity' => 1,
        'discount' => 0,
    ];

    protected function getForms(): array
    {
        return [
            'saleForm',
        ];
    }

    public function mount(): void
    {
        $this->ensureSaleExists();
        $this->saleForm->fill($this->saleData);
    }

    public function saleForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('cpf')
                    ->label('CPF do cliente (opcional)')
                    ->placeholder('Digite se quiser identificar')
                    ->mask('999.999.999-99')
                    ->helperText('Se informar, facilita historico de compra e trocas futuras.')
                    ->dehydrateStateUsing(fn (?string $state) => $state ? preg_replace('/\D+/', '', $state) : null)
                    ->live()
                    ->afterStateUpdated(fn ($state) => $this->handleCpfUpdated($state)),

                Select::make('product_id')
                    ->label('Produto')
                    ->placeholder('Digite o codigo ou nome do produto')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) =>
                        Product::query()
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->limit(20)
                            ->pluck('name', 'id')
                    )
                    ->getOptionLabelUsing(fn ($value): ?string =>
                        Product::find($value)?->name
                    )
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
                    ->helperText('Se nao tiver desconto, deixe 0.'),
            ])
            ->statePath('saleData');
    }

    protected function ensureSaleExists(): void
    {
        if ($this->sale) {
            return;
        }

        $this->sale = Sale::create([
            'status' => 'draft',
            'user_id' => auth()->id(),
            'client_id' => null,
            'total' => 0,
        ]);
    }

    protected function handleCpfUpdated(?string $value): void
    {
        $cpf = $value ? preg_replace('/\D+/', '', $value) : null;

        if (! $cpf || ! $this->sale) {
            return;
        }

        $client = Client::firstOrCreate(['cpf' => $cpf]);
        $this->sale->update(['client_id' => $client->id]);
    }

    public function addItem(): void
    {
        $this->ensureSaleExists();

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
                ->title('Produto indisponivel')
                ->danger()
                ->send();
            return;
        }

        if ($product->stock < $quantity) {
            Notification::make()
                ->title('Estoque insuficiente')
                ->body("Disponivel: {$product->stock}")
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
        if (! $this->sale) {
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
            'cpf' => null,
            'product_id' => null,
            'quantity' => 1,
            'discount' => 0,
        ];

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

            $this->sale = null;
            $this->saleData = ['cpf' => null, 'product_id' => null, 'quantity' => 1, 'discount' => 0];
            $this->saleForm->fill($this->saleData);
            $this->ensureSaleExists();
        } catch (\DomainException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();
        }
    }
}



