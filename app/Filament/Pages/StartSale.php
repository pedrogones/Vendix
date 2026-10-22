<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Services\Stocks\ConfirmSaleService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
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
                    ->getSearchResultsUsing(fn (string $search) => Product::query()
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->limit(20)
                        ->pluck('name', 'id')
                    )
                    ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->name
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

        \Illuminate\Support\Facades\Gate::authorize('create-sales');
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

        if (strlen($cpf) !== 11) {
            return;
        }
        \Illuminate\Support\Facades\DB::transaction(function () use ($cpf): void {
            $sale = Sale::query()->lockForUpdate()->findOrFail($this->sale->id);
            \Illuminate\Support\Facades\Gate::authorize($sale->user_id === auth()->id() ? 'create-sales' : 'edit-sales');
            if ($sale->status !== 'draft') {
                return;
            }
            $client = Client::firstOrCreate(['cpf' => $cpf]);
            $sale->update(['client_id' => $client->id]);
        });
    }

    public function addItem(): void
    {
        $this->ensureSaleExists();

        $this->validate([
            'saleData.product_id' => 'required|exists:products,id',
            'saleData.quantity' => 'required|integer|min:1',
            'saleData.discount' => 'nullable|numeric|min:0',
        ]);

        try {
            app(\App\Services\Sales\SaleItemService::class)->add(
                $this->sale,
                (int) $this->saleData['product_id'],
                (int) $this->saleData['quantity'],
                (float) ($this->saleData['discount'] ?? 0),
            );
        } catch (\DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();

            return;
        }

        $this->resetSaleForm();
        $this->sale->refresh()->load('items.product');
    }

    public function removeItem(int $itemId): void
    {
        if (! $this->sale) {
            return;
        }

        try {
            app(\App\Services\Sales\SaleItemService::class)->remove($this->sale, $itemId);
        } catch (\DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }

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
            \Illuminate\Support\Facades\Gate::authorize($this->sale->user_id === auth()->id() ? 'create-sales' : 'edit-sales');
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
