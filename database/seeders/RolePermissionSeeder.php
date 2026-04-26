<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
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
        // Bullet-proof cache reset: artisan command flushes the Spatie cache
        // through the Laravel cache store too (Redis in our setup), which is
        // necessary when the seeder runs after a `migrate:fresh` (the in-memory
        // registrar cache alone leaves stale entries in the cache backend).
        Artisan::call('permission:cache-reset');
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.assign',
            'pages.view', 'pages.create', 'pages.edit', 'pages.delete', 'pages.publish',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'audit.view',
        ];

        // firstOrCreate goes straight through Eloquent without consulting the
        // Spatie cache layer — robust against any stale cache state.
        foreach ($permissions as $name) {
            Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $admin = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions($permissions);

        $editor = Role::query()->firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        $editor->syncPermissions([
            'pages.view', 'pages.create', 'pages.edit', 'pages.publish',
            'products.view', 'products.create', 'products.edit',
        ]);

        Role::query()->firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        // 'user' has no permissions by default — uses model-level policies.
    }
}
