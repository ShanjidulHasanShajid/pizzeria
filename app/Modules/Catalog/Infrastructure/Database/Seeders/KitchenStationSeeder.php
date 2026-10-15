<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Seeders;

use App\Modules\Catalog\Domain\Entities\KitchenStation;
use App\Modules\Catalog\Domain\Repositories\KitchenStationRepository;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Database\Seeder;

/**
 * Where each item is made. Kitchen tickets group items by station.
 */
final class KitchenStationSeeder extends Seeder
{
    private const STATIONS = ['Oven', 'Fryer', 'Grill', 'Pasta', 'Cold Prep', 'Desserts', 'Drinks'];

    public function __construct(private readonly KitchenStationRepository $stations) {}

    public function run(): void
    {
        $sort = SortOrder::first();

        foreach (self::STATIONS as $name) {
            $this->stations->save(KitchenStation::create($name, $sort));
            $sort = $sort->next();
        }
    }
}
