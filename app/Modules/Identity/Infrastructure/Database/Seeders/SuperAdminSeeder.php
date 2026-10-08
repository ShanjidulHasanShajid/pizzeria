<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Database\Seeders;

use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Creates the first super admin from ADMIN_NAME, ADMIN_EMAIL and ADMIN_PASSWORD.
 * Safe to run again: an existing account is left untouched.
 */
final class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $name = (string) config('identity.super_admin.name');
        $email = mb_strtolower(trim((string) config('identity.super_admin.email')));
        $password = (string) config('identity.super_admin.password');

        if ($email === '' || $password === '') {
            throw new RuntimeException('Set ADMIN_EMAIL and ADMIN_PASSWORD in .env before seeding.');
        }

        if (User::withTrashed()->where('email', $email)->exists()) {
            return;
        }

        $admin = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        $admin->role = UserRole::SuperAdmin;
        $admin->email_verified_at = now();
        $admin->save();
    }
}
