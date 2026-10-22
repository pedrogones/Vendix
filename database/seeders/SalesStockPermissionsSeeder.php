<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// Safe to run on an existing installation: never changes users, passwords or existing grants.
class SalesStockPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $names = collect(['products', 'categories', 'sales', 'stock-movements'])
            ->crossJoin(['view', 'view-any', 'create', 'edit', 'delete'])
            ->map(fn ($pair) => $pair[1].'-'.$pair[0])->push('cancel-sales');
        $permissions = $names->map(fn (string $name) => Permission::findOrCreate($name, 'web'));
        Role::findOrCreate('Admin', 'web')->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
