<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Data a real shop needs on day one: settings, navigation, pages and the
 * super admin. Each module's production seeder is added here as it is built.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Phase 5: \App\Modules\Identity\Infrastructure\Database\Seeders\...
        ]);
    }
}
