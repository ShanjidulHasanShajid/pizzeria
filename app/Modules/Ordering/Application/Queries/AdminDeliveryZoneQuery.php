<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Queries;

use App\Modules\Ordering\Application\DTOs\DeliveryZoneRow;
use App\Modules\Ordering\Application\DTOs\TrashedDeliveryZone;

interface AdminDeliveryZoneQuery
{
    /**
     * Every zone that is not deleted, in order.
     *
     * @return list<DeliveryZoneRow>
     */
    public function table(): array;

    /** One zone that is not deleted, or null. */
    public function zone(int $id): ?DeliveryZoneRow;

    /**
     * @return list<TrashedDeliveryZone>
     */
    public function trashed(): array;
}
