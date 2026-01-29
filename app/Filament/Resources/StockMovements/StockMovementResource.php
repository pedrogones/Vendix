<?php

namespace App\Filament\Resources\StockMovements;

use App\Filament\Resources\BaseAclResource;
use App\Filament\Resources\StockMovements\Pages\ManageStockMovements;
use App\Models\Sale;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockMovementResource extends BaseAclResource
{
    protected static array $reasonLabels = [
        'sale' => 'Venda',
        'purchase' => 'Compra',
        'loss' => 'Perda',
        'manual' => 'Manual',
        'return' => 'Devolução',
    ];

    protected static array $typeLabels = [
        'in' => 'Entrada',
        'out' => 'Saída',
        'adjustment' => 'Ajuste',
    ];

    protected static array $referenceTypeLabels = [
        Sale::class => 'Venda',
        'sale' => 'Venda',
        'purchase' => 'Compra',
        'loss' => 'Perda',
        'return' => 'Devolução',
        'adjustment' => 'Ajuste',
        'manual' => 'Manual',
    ];

    protected static ?string $model = StockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $recordTitleAttribute = 'id';
    protected static ?string $modelLabel = 'Movimentação de estoque';
    protected static ?string $pluralModelLabel = 'Movimentações de estoque';
    protected static string $permissionEntity = 'stock-movements';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Produto')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('user_id')
                    ->label('Usuário')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn () => auth()->id())
                    ->required(),
                TextInput::make('reference_id')
                    ->label('ID de referência')
                    ->numeric()
                    ->nullable(),
                TextInput::make('quantity')
                    ->label('Quantidade')
                    ->required()
                    ->numeric(),
                Select::make('reason')
                    ->label('Motivo')
                    ->options(static::$reasonLabels)
                    ->required(),
                TextInput::make('reference')
                    ->label('Referência')
                    ->maxLength(255)
                    ->nullable(),
                Select::make('type')
                    ->label('Tipo de movimento')
                    ->options(static::$typeLabels)
                    ->required(),
                Select::make('reference_type')
                    ->label('Origem')
                    ->options(static::$referenceTypeLabels)
                    ->default('manual')
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('product.name')
                    ->label('Produto')
                    ->placeholder('-'),
                TextEntry::make('user.name')
                    ->label('Usuário')
                    ->placeholder('-'),
                TextEntry::make('reference_id')
                    ->label('ID de referência')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('quantity')
                    ->label('Quantidade')
                    ->numeric(),
                TextEntry::make('reason')
                    ->label('Motivo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => static::$reasonLabels[$state] ?? $state),
                TextEntry::make('reference')
                    ->label('Referência')
                    ->placeholder('-'),
                TextEntry::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => static::$typeLabels[$state] ?? $state),
                TextEntry::make('reference_type')
                    ->label('Origem')
                    ->badge()
                    ->formatStateUsing(fn ($state) => static::$referenceTypeLabels[$state] ?? $state),
                TextEntry::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')->label('Produto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')->label('Usuário')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')->label('Quantidade')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reason')->label('Motivo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => static::$reasonLabels[$state] ?? $state),
                TextColumn::make('reference')->label('Referência')
                    ->searchable(),
                TextColumn::make('type')->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => static::$typeLabels[$state] ?? $state),
                TextColumn::make('reference_type')->label('Origem')
                    ->badge()
                    ->formatStateUsing(fn ($state) => static::$referenceTypeLabels[$state] ?? $state),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStockMovements::route('/'),
        ];
    }
}
