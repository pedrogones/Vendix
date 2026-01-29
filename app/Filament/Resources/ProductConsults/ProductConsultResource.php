<?php

namespace App\Filament\Resources\ProductConsults;

use App\Filament\Resources\BaseAclResource;
use App\Filament\Resources\ProductConsults\Pages\ManageProductConsults;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductConsultResource extends BaseAclResource
{
    protected static ?string $model = Product::class;
    protected static string $permissionEntity = 'products';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;
    protected static ?string $navigationLabel = 'Consulta de Produtos';
    protected static ?string $modelLabel = 'Consulta de Produto';
    protected static ?string $pluralModelLabel = 'Consulta de Produtos';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Produto')
                    ->columns(2)
                    ->components([
                        ImageEntry::make('image.url')
                            ->label('Imagem')
                            ->height(110)
                            ->columnSpanFull(),
                        TextEntry::make('name')
                            ->label('Nome')
                            ->weight('bold'),
                        TextEntry::make('description')
                            ->label('Descrição')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('barcode')
                            ->label('Código de barras')
                            ->placeholder('-'),
                        TextEntry::make('sku')
                            ->label('SKU')
                            ->placeholder('-'),
                        TextEntry::make('category.name')
                            ->label('Categoria')
                            ->placeholder('-'),
                    ]),
                Section::make('Preço e estoque')
                    ->columns(3)
                    ->components([
                        TextEntry::make('price')
                            ->label('Preço')
                            ->money('BRL'),
                        TextEntry::make('promotional_price')
                            ->label('Preço promocional')
                            ->money('BRL')
                            ->placeholder('-'),
                        TextEntry::make('is_on_sale')
                            ->label('Em promoção')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Sim' : 'Não')
                            ->color(fn ($state) => $state ? 'success' : 'gray'),
                        TextEntry::make('stock')
                            ->label('Estoque'),
                        TextEntry::make('min_stock')
                            ->label('Estoque mínimo'),
                        TextEntry::make('is_active')
                            ->label('Ativo')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Sim' : 'Não')
                            ->color(fn ($state) => $state ? 'success' : 'danger'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('name')
            ->columns([
                ImageColumn::make('image.url')
                    ->label('')
                    ->size(36)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('barcode')
                    ->label('Código de barras')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Produto')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(40),
                TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(50)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('final_price')
                    ->label('Preço')
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('promotional_price')
                    ->label('Preço promocional')
                    ->money('BRL')
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('is_on_sale')
                    ->label('Promoção')
                    ->formatStateUsing(fn ($state) => $state ? 'Sim' : 'Não')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('is_active')
                    ->label('Ativo')
                    ->formatStateUsing(fn ($state) => $state ? 'Sim' : 'Não')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
                TextColumn::make('stock')
                    ->label('Estoque')
                    ->sortable(),
                TextColumn::make('min_stock')
                    ->label('Estoque mínimo')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProductConsults::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
