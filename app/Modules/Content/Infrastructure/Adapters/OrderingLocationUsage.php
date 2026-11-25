<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Adapters;

use App\Modules\Content\Application\Ports\LocationUsage;
use App\Modules\Ordering\Application\Queries\DeliveryZoneUsageQuery;

final class OrderingLocationUsage implements LocationUsage
{
    public function __construct(private readonly DeliveryZoneUsageQuery $zones) {}

    public function hasDeliveryZones(int $locationId): bool
    {
        return $this->zones->anyForLocation($locationId);
    }
}
