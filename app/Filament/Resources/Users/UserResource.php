<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\BaseAclResource;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends BaseAclResource
{

    protected static ?string $model = User::class;
    protected static string $permissionEntity = 'users';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'Usuário';
    protected static ?string $pluralModelLabel = 'Usuários';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

               Section::make([
                   FileUpload::make('avatar_url')
                   ->label('Foto de perfil')
                   ->image()
                   ->disk('public')
                   ->directory('avatars')
                   ->imageEditor() // opcional
                   ->maxSize(2048)
                   ->acceptedFileTypes(['image/jpeg', 'image/png'])
                   ->helperText('PNG ou JPG até 2MB')
                   ->visibility('public'),]),
                Section::make([
                    TextInput::make('name')->label('Nome')
                    ->required(),
                    TextInput::make('email')
                        ->label('E-mail')
                        ->email()
                        ->required(),
                    Select::make('roles')->label('Perfis')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable(),
                    TextInput::make('password')->label('Senha')
                        ->password()
                        ->required()->visibleOn('create')]
                ),

            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações do Usuário')
                    ->schema([
                        TextEntry::make('name')->label('Nome'),
                        TextEntry::make('email')->label('E-mail'),
                    ]),

                Section::make('Perfis (Roles)')
                    ->schema([
                        TextEntry::make('roles.name')
                            ->label('Perfis')->badge()
                            ->separator(','),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('Foto')
                    ->disk('public')
                    ->size(40)
                    ->circular()
                    ->defaultImageUrl(getUserDefaultAvatar()),

                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Perfis')->badge()
                    ->separator(','),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('edit-users')),

                DeleteAction::make()->visible(fn () => auth()->user()->can('delete-users')),
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
            'index' => ManageUsers::route('/'),
        ];
    }

}
