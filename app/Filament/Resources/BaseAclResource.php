<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;

abstract class BaseAclResource extends Resource
{
    protected static string $permissionEntity;

    protected static function permission(string $action): string
    {
        return "{$action}-" . static::$permissionEntity;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can(
            static::permission('view')
        ) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can(
            static::permission('view-any')
        );
    }

    public static function canCreate(): bool
    {

        return auth()->user()->can(
            static::permission('create')
        );
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can(
            static::permission('edit')
        );
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can(
            static::permission('delete')
        );
    }
}
