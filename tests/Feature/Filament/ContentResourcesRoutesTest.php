<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('admin reaches the Pages index', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/pages')->assertOk();
});

it('admin reaches the Articles index', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/articles')->assertOk();
});

it('admin reaches the FAQs index', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/faqs')->assertOk();
});

it('admin reaches the Page create form', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/pages/create')->assertOk();
});

it('plain user is forbidden from /admin/pages', function (): void {
    $u = User::factory()->create();
    $u->assignRole('user');
    $this->actingAs($u)->get('/admin/pages')->assertForbidden();
});
