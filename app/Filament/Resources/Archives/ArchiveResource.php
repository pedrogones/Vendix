<?php

namespace App\Filament\Resources\Archives;

use App\Filament\Resources\Archives\Pages\ManageArchives;
use App\Filament\Resources\BaseAclResource;
use App\Models\Archive;
use App\Services\Archives\ArchiveUploadService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArchiveResource extends BaseAclResource
{
    protected static ?string $model = Archive::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;
    protected static string $permissionEntity = 'archives';
    protected static ?string $recordTitleAttribute = 'original_name';
    protected static ?string $modelLabel = 'Arquivo';
    protected static ?string $pluralModelLabel = 'Arquivos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Arquivo')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Selecionar arquivo')
                            ->required()
                            ->disk('public')
                            ->directory('archives')
                            ->preserveFilenames(false)
                            ->maxSize(10240)
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                            ])
                            ->helperText('PDF, JPG ou PNG (máx 10MB)')
                            ->storeFiles(false)
                            ->visibleOn('create'),
                        Placeholder::make('arquivo_atual')
                            ->label('Arquivo atual')
                            ->content(fn ($record) => view('filament.archives.preview', [
                                'record' => $record,
                            ]))
                            ->visibleOn('edit'),
                    ]),

                Section::make('Informações')
                    ->schema([
                        Select::make('type')
                            ->label('Tipo do arquivo')
                            ->options([
                                'document' => 'Documento',
                                'image' => 'Imagem',
                                'contract' => 'Contrato',
                            ])
                            ->required(),

                        Select::make('category')
                            ->label('Categoria')
                            ->options([
                                'cpf' => 'CPF',
                                'rg' => 'RG',
                                'foto' => 'Foto',
                                'comprovante' => 'Comprovante',
                            ])
                            ->searchable(),

                        Select::make('visibility')
                            ->label('Visibilidade')
                            ->options([
                                'private' => 'Privado',
                                'public' => 'Público',
                            ])
                            ->default('private'),
                        Toggle::make('status')->label('Ativo?')
                            ->required(),
                    ]),
            ]);
    }


    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Arquivo')
                    ->schema([
                        Placeholder::make('preview')
                            ->label('Pré-visualização')
                            ->content(fn ($record) => view('filament.archives.preview', [
                                'record' => $record,
                            ])),
                    ]),

                Section::make('Informações')
                    ->schema([
                        TextEntry::make('original_name')
                            ->label('Nome original')
                            ->copyable(),

                        TextEntry::make('type')
                            ->label('Tipo')
                            ->badge(),

                        TextEntry::make('category')
                            ->label('Categoria')
                            ->badge(),

                        TextEntry::make('visibility')
                            ->label('Visibilidade')
                            ->badge(),

                        IconEntry::make('status')
                            ->label('Ativo')
                            ->boolean(),
                    ])
                    ->columns(2),

                Section::make('Detalhes técnicos')
                    ->schema([
                        TextEntry::make('mime_type')
                            ->label('Tipo MIME')
                            ->badge(),

                        TextEntry::make('extension')
                            ->label('Extensão'),

                        TextEntry::make('size')
                            ->label('Tamanho')
                            ->formatStateUsing(fn ($state) => number_format($state / 1024, 2) . ' KB'),

                        TextEntry::make('disk')
                            ->label('Disco'),

                        TextEntry::make('path')
                            ->label('Caminho')
                            ->copyable(),
                    ])
                    ->columns(2),

                Section::make('Auditoria')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('uploadedBy.name')
                            ->label('Enviado por'),
                    ])
                    ->columns(2),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->columns([
                ImageColumn::make('path')
                    ->label('Pré-visualização')
                    ->disk('public')
                    ->size(48)
                    ->square()
                    ->getStateUsing(function ($record) {
                        if (! $record->mime_type) {
                            return getDefaultNoFile();
                        }

                        if (str_contains($record->mime_type, 'image')) {
                            return $record->path;
                        }

                        if ($record->mime_type === 'application/pdf') {
                            return getPdfDefaultImage();
                        }

                        return getDefaultNoFile();
                    }),
                TextColumn::make('original_name')
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab()
                    ->limit(30),

                TextColumn::make('type')
                    ->badge(),
                IconColumn::make('status')
                    ->boolean(),
                TextColumn::make('category')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->authorize(fn ($record) => static::canEdit($record)),
                DeleteAction::make()->authorize(fn ($record) => static::canDelete($record)),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageArchives::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

}
