<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Resources\BaseAclResource;
use App\Filament\Resources\Clients\Pages\ManageClients;
use App\Models\Client;
use BackedEnum;
use chillerlan\QRCode\Common\MaskPattern;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rule;

class ClientResource extends BaseAclResource
{
    protected static ?string $model = Client::class;
    protected static string $permissionEntity = 'clients';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
    protected static string|null|\UnitEnum $navigationGroup = 'Clientes';

    protected static ?string $recordTitleAttribute = 'cpf';
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clientes';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user.name')
                    ->label('Usuário')
                    ->required()
                    ->readOnly()
                    ->afterStateHydrated(function ($component, $state, $record) {
                        $component->state($record->user?->name ?? '');
                    })
                    ->dehydrated(false),
                TextInput::make('cpf')->id('cpf')
                    ->label('CPF')
                    ->required()
                    ->maxLength(14)
                    ->rules([
                        fn($record) => Rule::unique('clients', 'cpf')
                            ->ignore($record?->id),
                    ])
                    ->validationMessages([
                        'unique' => 'Este CPF já está cadastrado.',
                    ]),
                TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->rules([
                        fn($record) => Rule::unique('clients', 'email')
                            ->ignore($record?->id),
                    ])
                    ->validationMessages([
                        'unique' => 'Este e-mail já está cadastrado.',
                    ])->required(),
                TextInput::make('phone')
                    ->tel()
                    ->id('phone')
                    ->label('Telefone')
                    ->default(null),
                Toggle::make('status')
                    ->label('Ativo')
                    ->required(),
                DatePicker::make('birth_date')
                    ->label('Data de nascimento'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('Usuário'),
                TextEntry::make('cpf')->label('CPF'),
                TextEntry::make('email')
                    ->label('E-mail'),
                TextEntry::make('phone')
                    ->label('Telefone')
                    ->placeholder('-'),
                IconEntry::make('status')
                    ->boolean(),
                TextEntry::make('gender')
                    ->label('Gênero')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('birth_date')
                    ->label('Data de nascimento')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn(Client $record): bool => $record->trashed()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('cpf')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuário')
                    ->searchable(),
                TextColumn::make('cpf')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Telefone')
                    ->searchable(),
                IconColumn::make('status')
                    ->boolean(),
                TextColumn::make('gender')
                    ->label('Gênero')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('birth_date')
                    ->label('Data de nascimento')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => ManageClients::route('/'),
        ];
    }


}

