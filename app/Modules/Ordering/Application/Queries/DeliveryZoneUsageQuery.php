<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Queries;

/**
 * For other modules: is something still needed by a delivery zone?
 */
interface DeliveryZoneUsageQuery
{
    /** True when any zone belongs to this branch, also a zone in the Trash. */
    public function anyForLocation(int $locationId): bool;
}
