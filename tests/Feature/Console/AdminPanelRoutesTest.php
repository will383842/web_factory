<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('redirects unauthenticated visits on /admin to the login page', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('serves the Filament login form on /admin/login', function (): void {
    $this->get('/admin/login')->assertOk();
});

it('lets an admin reach /admin/users', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk();
});

it('lets an admin reach /admin/roles', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/admin/roles')
        ->assertOk();
});

it('lets an admin reach the GeneralSettings page', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/admin/manage-general-settings')
        ->assertOk();
});

it('forbids a "user" role from reaching the panel', function (): void {
    $regular = User::factory()->create();
    $regular->assignRole('user');

    // canAccessPanel returns false for the "user" role -> Filament 403s before render
    $this->actingAs($regular)
        ->get('/admin')
        ->assertForbidden();
});
