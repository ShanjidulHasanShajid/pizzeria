<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure\Persistence\Mappers;

use App\Modules\Ordering\Domain\Entities\DeliveryZone;
use App\Modules\Ordering\Domain\ValueObjects\DeliveryZoneDetails;
use App\Modules\Ordering\Infrastructure\Persistence\Models\DeliveryZone as DeliveryZoneModel;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

final class DeliveryZoneMapper
{
    public function toEntity(DeliveryZoneModel $model): DeliveryZone
    {
        return DeliveryZone::reconstitute(
            $model->id,
            DeliveryZoneDetails::reconstitute(
                $model->name,
                $model->location_id,
                Money::fromPoisha($model->fee),
                Money::fromPoisha($model->min_order),
            ),
            $model->is_active,
            SortOrder::fromInt($model->sort),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toRow(DeliveryZone $zone): array
    {
        return [
            'location_id' => $zone->details()->locationId,
            'name' => $zone->details()->name,
            'fee' => $zone->details()->fee->poisha(),
            'min_order' => $zone->details()->minimumOrder->poisha(),
            'sort' => $zone->sort()->value(),
            'is_active' => $zone->isActive(),
        ];
    }
}
