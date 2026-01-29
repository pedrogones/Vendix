<?php

namespace App\Filament\Resources\Sales\Pages;


use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SaleItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    // no model Sale: hasMany(SaleItem::class, 'sale_id')

    protected static ?string $title = 'Itens da Venda';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Produto')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) =>
                    $set('unit_price', \App\Models\Product::find($state)?->final_price)
                    ),

                Forms\Components\TextInput::make('quantity')
                    ->label('Quantidade')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (Get $get, Set $set) =>
                        $set('total_price', max(0, ($get('quantity') ?? 1) * ($get('unit_price') ?? 0) - ($get('discount') ?? 0)))
                    ),

                Forms\Components\TextInput::make('unit_price')
                    ->label('Preço unitário')
                    ->numeric()
                    ->prefix('R$')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (Get $get, Set $set) =>
                        $set('total_price', max(0, ($get('quantity') ?? 1) * ($get('unit_price') ?? 0) - ($get('discount') ?? 0)))
                    ),

                Forms\Components\TextInput::make('discount')
                    ->label('Desconto')
                    ->numeric()
                    ->prefix('R$')
                    ->default(0)
                    ->reactive()
                    ->afterStateUpdated(fn (Get $get, Set $set) =>
                        $set('total_price', max(0, ($get('quantity') ?? 1) * ($get('unit_price') ?? 0) - ($get('discount') ?? 0)))
                    ),

                Forms\Components\TextInput::make('total_price')
                    ->label('Total')
                    ->numeric()
                    ->prefix('R$')
                    ->disabled()       // não editável no front
                    ->dehydrated(),
            ])
            ->columns(2)
            ->live();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['discount'] = $data['discount'] ?? 0;
        $data['total_price'] = max(0, ($data['quantity'] * $data['unit_price']) - $data['discount']);
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['discount'] = $data['discount'] ?? 0;
        $data['total_price'] = max(0, ($data['quantity'] * $data['unit_price']) - $data['discount']);
        return $data;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Produto'),

                TextColumn::make('quantity')
                    ->label('Qtd'),

                TextColumn::make('unit_price')
                    ->label('Unitário')
                    ->money('BRL'),

                TextColumn::make('discount')
                    ->label('Desconto')
                    ->money('BRL')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_price')
                    ->label('Total')
                    ->money('BRL'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    protected function canCreate(): bool
    {
        return $this->ownerRecord->status === 'draft';
    }

    protected function canEdit($record): bool
    {
        return $this->ownerRecord->status === 'draft';
    }

    protected function canDelete($record): bool
    {
        return $this->ownerRecord->status === 'draft';
    }

    protected function recalcSaleTotal(): void
    {
        $total = $this->ownerRecord
            ->items()
            ->sum('total_price');

        $this->ownerRecord->update([
            'total' => $total,
        ]);
    }

    protected function afterCreate(): void
    {
        $this->recalcSaleTotal();
    }

    protected function afterEdit(): void
    {
        $this->recalcSaleTotal();
    }

    protected function afterDelete(): void
    {
        $this->recalcSaleTotal();
    }
}
