<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure\Database\Seeders;

use App\Modules\Ordering\Application\DTOs\Branch;
use App\Modules\Ordering\Application\DTOs\SaveDeliveryZoneInput;
use App\Modules\Ordering\Application\Ports\Branches;
use App\Modules\Ordering\Application\UseCases\DeliveryZones\CreateDeliveryZone;
use App\Modules\Ordering\Domain\Entities\DeliveryZone;
use App\Modules\Ordering\Infrastructure\Persistence\Models\DeliveryZone as DeliveryZoneModel;
use Illuminate\Database\Seeder;

/**
 * Sample delivery zones for the first two active branches. Development only, after the content.
 * Runs once: if there is any zone (also in the Trash), nothing happens.
 */
final class OrderingDevelopmentSeeder extends Seeder
{
    private const AREAS = [['Gulshan', 'Banani'], ['Dhanmondi', 'Mohammadpur']];

    public function __construct(
        private readonly Branches $branches,
        private readonly CreateDeliveryZone $createZone,
    ) {}

    public function run(): void
    {
        if (DeliveryZoneModel::withTrashed()->exists()) {
            return;
        }

        $branches = array_values(array_filter($this->branches->all(), fn (Branch $branch): bool => $branch->isActive));
        $fee = DeliveryZone::startingFee()->toTakaString();

        foreach (array_slice($branches, 0, count(self::AREAS)) as $index => $branch) {
            foreach (self::AREAS[$index] as $area) {
                $this->createZone->handle(new SaveDeliveryZoneInput(null, $area, $branch->id, $fee, '300', true));
            }
        }
    }
}
