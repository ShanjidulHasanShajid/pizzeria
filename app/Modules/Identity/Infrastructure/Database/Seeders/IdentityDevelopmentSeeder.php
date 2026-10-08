<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Database\Seeders;

use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo accounts for local development only. Every password is "password".
 */
final class IdentityDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (User::withTrashed()->where('email', 'staff@pizzeria.test')->exists()) {
            return;
        }

        User::factory()->staff()->create(['name' => 'Demo Staff', 'email' => 'staff@pizzeria.test']);
        User::factory()->admin()->create(['name' => 'Demo Admin', 'email' => 'admin@pizzeria.test']);
        User::factory()->create(['name' => 'Demo Customer', 'email' => 'customer@pizzeria.test', 'phone' => '01711111111']);
        User::factory()->blocked()->create(['name' => 'Blocked Customer', 'email' => 'blocked@pizzeria.test']);
        User::factory()->count(12)->create();
    }
}
