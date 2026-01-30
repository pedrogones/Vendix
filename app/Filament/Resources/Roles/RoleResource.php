<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\BaseAclResource;
use App\Filament\Resources\Roles\Pages\ManageRoles;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class RoleResource extends BaseAclResource
{
    protected static ?string $model = Role::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;
    protected static string|null|\UnitEnum $navigationGroup = 'Seguranca';

    protected static string $permissionEntity = 'roles';

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'Perfil';
    protected static ?string $pluralModelLabel = 'Perfis';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Perfil')
                    ->required()
                    ->maxLength(255),
                CheckboxList::make('permissions')
                    ->label('Permissões')
                    ->relationship('permissions', 'name')
                    ->columns(3)
                    ->searchable()
                    ->bulkToggleable()
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações do Perfil')
                    ->schema([
                        TextEntry::make('name')->label('Perfil'),
                        TextEntry::make('guard_name')->label('Tipo'),
                    ]),

                Section::make('Permissões')
                    ->schema([
                        TextEntry::make('permissions.name')
                            ->label('Permissões')->badge()
                            ->separator(','),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Perfil')
                    ->searchable(),
                TextColumn::make('guard_name')->label('Tipo')->badge()
                    ->searchable(),
                TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Permissões')->badge()
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn () => auth()->user()->can('edit-roles')),
                DeleteAction::make()->visible(fn () => auth()->user()->can('delete-roles')),
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
            'index' => ManageRoles::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery();
    }

}

