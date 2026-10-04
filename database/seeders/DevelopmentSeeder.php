<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Fake menu, customers and orders for local development only.
 * Never run on a real database.
 */
class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProductionSeeder::class,
            // Phase 6 and later: module development seeders (menu, sample orders).
        ]);
    }
}
