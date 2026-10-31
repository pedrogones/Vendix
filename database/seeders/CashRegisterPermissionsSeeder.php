<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// Safe for existing installs: never changes users or passwords.
class CashRegisterPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'view-cash-register',
            'open-cash',
            'close-cash',
            'manage-cash-movements',
        ];
        $permissions = collect($names)->map(fn (string $name) => Permission::findOrCreate($name, 'web'));
        Role::findOrCreate('Admin', 'web')->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
