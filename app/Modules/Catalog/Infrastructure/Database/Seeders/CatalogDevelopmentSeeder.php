<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Seeders;

use App\Modules\Catalog\Infrastructure\Persistence\Models\KitchenStation;
use Illuminate\Database\Seeder;

/**
 * The whole sample menu, in the order the data depends on each other.
 * Development only. Runs once: if stations exist, the catalog is already seeded.
 */
final class CatalogDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (KitchenStation::withTrashed()->exists()) {
            return;
        }

        $this->call([
            KitchenStationSeeder::class,
            SizeTierSeeder::class,
            TagSeeder::class,
            CategorySeeder::class,
            PizzaSeeder::class,
            PieSeeder::class,
            MenuSeeder::class,
            ProductImageSeeder::class,
        ]);
    }
}
