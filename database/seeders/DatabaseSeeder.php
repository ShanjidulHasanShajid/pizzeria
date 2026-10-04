<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * `php artisan db:seed` picks the right set for the environment:
     * fake data locally, only essential data everywhere else.
     */
    public function run(): void
    {
        $this->call(
            app()->environment('local') ? DevelopmentSeeder::class : ProductionSeeder::class,
        );
    }
}
