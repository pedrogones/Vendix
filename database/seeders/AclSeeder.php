<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AclSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [

            // Dashboard
            'view-dashboard',

            // Users
            'view-users',
            'view-any-users',
            'create-users',
            'edit-users',
            'delete-users',

            // Roles
            'view-roles',
            'view-any-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',

            // Permissions
            'view-permissions',
            'view-any-permissions',
            'create-permissions',
            'edit-permissions',
            'delete-permissions',

            // Files
            'view-files',
            'view-any-files',
            'upload-files',
            'download-files',
            'edit-files',
            'delete-files',

            // Posts
            'view-posts',
            'view-any-posts',
            'create-posts',
            'edit-posts',
            'delete-posts',
            'publish-posts',
            'unpublish-posts',

            // Clients
            'view-clients',
            'view-any-clients',
            'create-clients',
            'edit-clients',
            'delete-clients',

            // Reports
            'view-reports',
            'export-reports',

            // Settings
            'view-settings',
            'edit-settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $admin = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);
        $admin->syncPermissions(Permission::all());

        // CLIENTE
        Role::firstOrCreate([
            'name' => 'Cliente',
            'guard_name' => 'web',
        ]);

        // GESTOR
        $manager = Role::firstOrCreate([
            'name' => 'Gestor',
            'guard_name' => 'web',
        ]);
        $manager->syncPermissions([
            'view-dashboard',

            'view-users',
            'view-any-users',
            'create-users',
            'edit-users',

            'view-posts',
            'view-any-posts',
            'create-posts',
            'edit-posts',
            'publish-posts',

            'view-clients',
            'view-any-clients',
            'create-clients',
            'edit-clients',

            'view-reports',
            'export-reports',
        ]);

        // OPERADOR
        $operator = Role::firstOrCreate([
            'name' => 'Operador',
            'guard_name' => 'web',
        ]);
        $operator->syncPermissions([
            'view-dashboard',

            'view-posts',
            'view-any-posts',
            'create-posts',
            'edit-posts',

            'view-clients',
            'view-any-clients',
            'create-clients',
        ]);

        // LEITOR
        $viewer = Role::firstOrCreate([
            'name' => 'Leitor',
            'guard_name' => 'web',
        ]);
        $viewer->syncPermissions([
            'view-dashboard',

            'view-posts',
            'view-any-posts',

            'view-clients',
            'view-any-clients',
        ]);
    }
}
