<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Catalog\Infrastructure\Database\Seeders\CatalogDevelopmentSeeder;
use App\Modules\Content\Infrastructure\Database\Seeders\ContentDevelopmentSeeder;
use App\Modules\Customization\Infrastructure\Database\Seeders\CustomizationDevelopmentSeeder;
use App\Modules\Identity\Infrastructure\Database\Seeders\IdentityDevelopmentSeeder;
use App\Modules\Ordering\Infrastructure\Database\Seeders\OrderingDevelopmentSeeder;
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
            CustomizationDevelopmentSeeder::class, // after the catalog: it attaches groups to products
            ContentDevelopmentSeeder::class, // after the catalog: the home layout shows its categories
            OrderingDevelopmentSeeder::class, // after the content: zones belong to branches
            // Later phases: more module development seeders (sample orders ...).
        ]);
    }
}
