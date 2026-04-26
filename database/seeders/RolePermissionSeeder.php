<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the canonical 3-role taxonomy and the baseline permission set.
 *
 * Roles:
 *  - admin   : full access to the platform
 *  - editor  : content / catalog editing, no user/role management
 *  - user    : end-user, read own profile only
 *
 * Permissions follow the dot-notation `<resource>.<action>` convention
 * (e.g., `users.view`, `pages.publish`).
 */
final class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset Spatie's permission cache so the seeder picks up fresh state.
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.assign',
            'pages.view', 'pages.create', 'pages.edit', 'pages.delete', 'pages.publish',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'audit.view',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name);
        }

        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions($permissions);

        $editor = Role::findOrCreate('editor');
        $editor->syncPermissions([
            'pages.view', 'pages.create', 'pages.edit', 'pages.publish',
            'products.view', 'products.create', 'products.edit',
        ]);

        Role::findOrCreate('user');
        // 'user' has no permissions by default — uses model-level policies.
    }
}
