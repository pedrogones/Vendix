<?php

namespace App\Filament\Resources\Profiles;

use App\Filament\Resources\BaseAclResource;
use App\Filament\Resources\Profiles\Pages\ManageProfiles;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class ProfileResource extends BaseAclResource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;
    protected static string|null|\UnitEnum $navigationGroup = 'Seguranca';

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'Perfil';
    protected static ?string $pluralModelLabel = 'Perfis';
    protected static string $permissionEntity = 'profiles';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required(),

                TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->rules([
                        fn($record) => Rule::unique('users', 'email')->ignore($record?->id),
                    ])
                    ->validationMessages([
                        'unique' => 'Este e-mail já está cadastrado.',
                    ])
                    ->required(),

                FileUpload::make('avatar_url')
                    ->label('Foto de perfil')
                    ->disk('public')
                    ->directory('avatars')
                    ->image()
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png'])
                    ->visibility('public'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProfiles::route('/'),
        ];
    }
}


