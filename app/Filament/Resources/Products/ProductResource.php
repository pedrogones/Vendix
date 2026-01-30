<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\BaseAclResource;
use App\Filament\Resources\Products\Pages\ManageProducts;
use App\Models\Product;
use App\Services\Archives\ArchiveUploadService;
use App\Services\Products\ProductLookupService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\View\Components\BadgeComponent;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\Str;
use Filament\Forms\Components\{
    TextInput,
    Toggle,
    Select,
    Textarea,
    FileUpload
};
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductResource extends BaseAclResource
{
    protected $listeners = [
        'barcodeScanned' => 'handleBarcode',
    ];


    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;
    protected static string|null|\UnitEnum $navigationGroup = 'Produtos';

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'Produto';
    protected static ?string $pluralModelLabel = 'Produtos';
    protected static string $permissionEntity = 'products';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Imagem')
                    ->components([
                        FileUpload::make('image_file')
                            ->label('Imagem do produto')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->storeFiles(false)
                            ->nullable()


                    ]),

                Section::make('Identificação')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Nome do produto')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                if (! filled($get('slug'))) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->disabled()
                            ->dehydrated(),

                        Textarea::make('description')
                            ->label('Descrição do produto'),

                        TextInput::make('barcode')
                            ->label('Código de barras')
                            ->unique(ignoreRecord: true)
                            ->helperText('EAN / QR Code / Scanner')
                            ->maxLength(100),
                    ]),

                Section::make('Categoria')
                    ->components([
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ]),

                Section::make('Preços')
                    ->columns(3)
                    ->components([
                        TextInput::make('price')
                            ->label('Preço')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),

                        Toggle::make('is_on_sale')
                            ->label('Em promoção')
                            ->live(),

                        TextInput::make('promotional_price')
                            ->label('Preço promocional')
                            ->numeric()
                            ->prefix('R$')
                            ->visible(fn ($get) => $get('is_on_sale'))
                            ->required(fn ($get) => $get('is_on_sale')),
                    ]),

                Section::make('Estoque')
                    ->columns(2)
                    ->components([
                        TextInput::make('stock')
                            ->label('Quantidade em estoque')
                            ->numeric()
                            ->default(0),

                        TextInput::make('min_stock')
                            ->label('Estoque mínimo')
                            ->numeric()
                            ->default(0),
                    ]),

                Section::make('Status')
                    ->columns(2)
                    ->components([
                        Toggle::make('is_active')
                            ->label('Ativo')
                            ->default(true),
                    ]),

                Section::make('Dados externos')
                    ->collapsed()
                    ->components([
                        Textarea::make('external_data')
                            ->label('Dados da API / Scanner')
                            ->rows(4)
                            ->disabled()
                            ->afterStateHydrated(function (Textarea $component, $state): void {
                                if (is_array($state)) {
                                    $component->state(json_encode(
                                        $state,
                                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                                    ));
                                }
                            })
                            ->dehydrateStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return $state;
                                }

                                if (is_string($state) && $state !== '') {
                                    $decoded = json_decode($state, true);
                                    return $decoded ?? $state;
                                }

                                return $state;
                            })
                            ->dehydrated(),
                    ]),
            ]);
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
                            ->height(120)
                            ->circular(false)
                            ->columnSpanFull(),

                        TextEntry::make('name')
                            ->label('Nome')
                            ->weight('bold'),

                        TextEntry::make('description')
                            ->label('Descrição do Produto')
                            ->weight('bold'),

                        TextEntry::make('category.name')
                            ->label('Categoria')
                            ->placeholder('-'),

                        TextEntry::make('barcode')
                            ->label('Código de barras')
                            ->placeholder('-'),
                    ]),

                Section::make('Preços')
                    ->columns(3)
                    ->components([
                        TextEntry::make('price')
                            ->label('Preço')
                            ->money('BRL'),

                        TextEntry::make('promotional_price')
                            ->label('Preço promocional')
                            ->money('BRL')
                            ->visible(fn ($record) => $record->is_on_sale),

                        TextEntry::make('is_on_sale')
                            ->label('Promoção')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Ativa' : 'Não')
                            ->color(fn ($state) => $state ? 'success' : 'gray'),
                    ]),

                Section::make('Estoque')
                    ->columns(3)
                    ->components([
                        TextEntry::make('stock')
                            ->label('Em estoque'),

                        TextEntry::make('min_stock')
                            ->label('Estoque mínimo'),

                        TextEntry::make('stock')
                            ->label('Status do estoque')
                            ->badge()
                            ->formatStateUsing(fn ($state, $record) =>
                            $record->isLowStock() ? 'Baixo' : 'OK'
                            )
                            ->color(fn ($state, $record) =>
                            $record->isLowStock() ? 'danger' : 'success'
                            ),
                    ]),

                Section::make('Status')
                    ->components([
                        TextEntry::make('is_active')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Ativo' : 'Inativo')
                            ->color(fn ($state) => $state ? 'success' : 'danger'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([

                ImageColumn::make('image.url')
                    ->label('')
                    ->size(40),

                TextColumn::make('name')
                    ->label('Produto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('barcode')
                    ->label('Código')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('final_price')
                    ->label('Preço')
                    ->money('BRL')
                    ->sortable(),

                BadgeColumn::make('is_on_sale')
                    ->label('Promoção')
                    ->formatStateUsing(fn ($state) => $state ? 'Sim' : 'Não')
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                BadgeColumn::make('stock')
                    ->label('Estoque')
                    ->color(fn ($state, $record) =>
                    $record->isLowStock() ? 'danger' : 'success'
                    ),
            ])
            ->filters([
                // depois dá pra filtrar por categoria, promoção, ativo
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, $record) {
                        if ($record->image?->path) {
                            $data['image_file'] = $record->image->path;
                        }

                        return $data;
                    })
                    ->using(function ($record, array $data) {
                        if (
                            ! empty($data['image_file']) &&
                            $data['image_file'] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile
                        ) {
                            $archive = app(\App\Services\Archives\ArchiveUploadService::class)->upload(
                                file: $data['image_file'],
                                type: 'image',
                                category: 'product',
                                visibility: 'public',
                            );

                            $data['image_id'] = $archive->id;
                        }
                        unset($data['image_file']);
                        $record->update($data);
                        return $record;
                    }),
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
            'index' => ManageProducts::route('/'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! empty($this->record?->image?->path)) {
            $data['image_file'] = $this->record->image->path;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['image_file']) && $data['image_file'] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {

            $archive = app(ArchiveUploadService::class)->upload(
                file: $data['image_file'],
                type: 'image',
                category: 'product',
                visibility: 'public',
            );

            $data['image_id'] = $archive->id;
        }

        unset($data['image_file']);

        return $data;
    }
//    public function handleBarcode(string $code): void
//    {
//        $this->barcode = $code;
//
//        $data = app(ProductLookupService::class)->lookup($code);
//
//        if (! $data) {
//            Notification::make()
//                ->danger()
//                ->title('Produto não encontrado')
//                ->send();
//            return;
//        }
//
//        $this->form->fill([
//            'name'        => $data['name'] ?? null,
//            'description' => $data['description'] ?? null,
//            'barcode'     => $code,
//            'external_data' => $data,
//        ]);
//    }


}

