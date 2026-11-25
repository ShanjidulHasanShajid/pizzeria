<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure\Queries;

use App\Modules\Ordering\Application\Queries\DeliveryZoneUsageQuery;
use App\Modules\Ordering\Infrastructure\Persistence\Models\DeliveryZone;

final class EloquentDeliveryZoneUsageQuery implements DeliveryZoneUsageQuery
{
    public function anyForLocation(int $locationId): bool
    {
        return DeliveryZone::withTrashed()->where('location_id', $locationId)->exists();
    }
}
