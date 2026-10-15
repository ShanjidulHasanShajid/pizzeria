<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Catalog\Infrastructure\Database\Seeders\CatalogDevelopmentSeeder;
use App\Modules\Identity\Infrastructure\Database\Seeders\IdentityDevelopmentSeeder;
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
            ProductionSeeder::class,   // already creates the super admin
            IdentityDevelopmentSeeder::class,
            CatalogDevelopmentSeeder::class,
            // Phase 7 and later: more module development seeders (sample orders ...).
        ]);
    }
}
