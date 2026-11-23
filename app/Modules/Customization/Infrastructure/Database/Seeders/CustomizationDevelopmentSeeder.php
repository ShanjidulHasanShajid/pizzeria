<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Database\Seeders;

use App\Modules\Customization\Infrastructure\Persistence\Models\OptionGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The sample option groups and where they are offered. Development only, after the catalog.
 * Runs once: if option groups exist, they are already seeded.
 */
final class CustomizationDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (OptionGroup::withTrashed()->exists()) {
            return;
        }

        // All or nothing: if a product is missing, no groups stay behind and it can run again.
        DB::transaction(fn () => $this->call([
            OptionGroupSeeder::class,
            ProductOptionGroupSeeder::class,
        ]));
    }
}
