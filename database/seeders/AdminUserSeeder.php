<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a default Filament admin user from the local .env file.
 * Guarded by environment check: NEVER runs outside of `local`.
 */
final class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal()) {
            $this->command?->warn('AdminUserSeeder skipped: APP_ENV is not "local".');

            return;
        }

        $email = (string) env('ADMIN_EMAIL', '');
        $password = (string) env('ADMIN_PASSWORD', '');

        if ($email === '' || $password === '') {
            $this->command?->warn(
                'AdminUserSeeder skipped: set ADMIN_EMAIL and ADMIN_PASSWORD in your local .env file.',
            );

            return;
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'WebFactory Admin',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        // Assign the admin role (idempotent — Spatie no-ops if already set)
        $user->assignRole('admin');

        $this->command?->info("Admin user ensured: {$email} (role: admin)");
    }
}
