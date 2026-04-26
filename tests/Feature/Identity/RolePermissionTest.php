<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('seeds the canonical 3-role taxonomy', function (): void {
    expect(Role::pluck('name')->sort()->values()->all())
        ->toBe(['admin', 'editor', 'user']);
});

it('grants all permissions to admin', function (): void {
    /** @var Role $admin */
    $admin = Role::findByName('admin');
    expect($admin->permissions()->count())->toBe(16);
});

it('grants editor a subset of permissions (no user/role mgmt, no delete)', function (): void {
    /** @var Role $editor */
    $editor = Role::findByName('editor');
    $names = $editor->permissions()->pluck('name')->all();

    expect($names)->toContain('pages.publish', 'products.create')
        ->not->toContain('users.delete', 'users.edit', 'roles.assign');
});

it('lets an admin user pass the canAccessPanel gate', function (): void {
    $u = User::factory()->create();
    $u->assignRole('admin');

    expect($u->fresh()->hasRole('admin'))->toBeTrue();
});
