<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\Stocks\StockService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class StockAlerts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Alertas de Estoque';
    protected static ?string $navigationLabel = 'Alertas de Estoque';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedExclamationTriangle;
    protected static string|null|\UnitEnum $navigationGroup = 'Estoque';
    protected static ?string $slug = 'alertas-estoque';

    protected string $view = 'filament.pages.stock-alerts';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view-dashboard') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view-dashboard') ?? false;
    }

    protected function getViewData(): array
    {
        $baseQuery = Product::query()->where('is_active', true);

        $lowStockCount = (int) (clone $baseQuery)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        $outOfStockCount = (int) (clone $baseQuery)
            ->where('stock', '<=', 0)
            ->count();

        $totalProducts = (int) (clone $baseQuery)->count();

        return [
            'kpis' => [
                'low' => $lowStockCount,
                'out' => $outOfStockCount,
                'total' => $totalProducts,
            ],
            'updated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultSort('stock', 'asc')
            ->columns([
                TextColumn::make('name')
                    ->label('Produto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->toggleable()
                    ->sortable(),
                BadgeColumn::make('stock')
                    ->label('Estoque')
                    ->color(fn (string|int $state, Product $record): string => $record->stock <= 0 ? 'danger' : 'warning')
                    ->sortable(),
                TextColumn::make('min_stock')
                    ->label('Minimo')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('shortage')
                    ->label('Reposicao')
                    ->getStateUsing(fn (Product $record): int => max(0, (int) $record->min_stock - (int) $record->stock))
                    ->numeric(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (Product $record): string => $record->stock <= 0 ? 'Esgotado' : 'Baixo')
                    ->color(fn (Product $record): string => $record->stock <= 0 ? 'danger' : 'warning'),
            ])
            ->filters([
                Filter::make('out')
                    ->label('Somente esgotados')
                    ->query(fn (Builder $query): Builder => $query->where('stock', '<=', 0)),
            ])
            ->recordActions([
                Action::make('restock')
                    ->label('Repor')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('quantity')
                            ->label('Quantidade')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->action(function (Product $record, array $data): void {
                        try {
                            app(StockService::class)->in(
                                product: $record,
                                quantity: (int) $data['quantity'],
                                reason: 'manual',
                            );

                            Notification::make()
                                ->title('Estoque atualizado')
                                ->success()
                                ->send();
                        } catch (\Throwable $exception) {
                            Notification::make()
                                ->title('Nao foi possivel atualizar o estoque')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('open')
                    ->label('Abrir produto')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (Product $record): string => ProductResource::getUrl('index', [
                        'search' => $record->name,
                    ])),
            ])
            ->emptyStateHeading('Nenhum alerta de estoque')
            ->emptyStateDescription('Todos os produtos estao acima do minimo.');
    }

    protected function getTableQuery(): Builder
    {
        return Product::query()
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock');
    }
}
