<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// Safe for existing installs: never changes users or passwords.
class ChatOrdersPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'view-chat-orders',
            'manage-chat-orders',
        ];
        $permissions = collect($names)->map(fn (string $name) => Permission::findOrCreate($name, 'web'));
        Role::findOrCreate('Admin', 'web')->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
