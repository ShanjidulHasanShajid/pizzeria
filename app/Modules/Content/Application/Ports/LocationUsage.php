<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\Ports;

/**
 * Is a branch still needed by another module?
 */
interface LocationUsage
{
    /** True when a delivery zone belongs to this branch, also a zone in the Trash. */
    public function hasDeliveryZones(int $locationId): bool;
}
