<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Presentation\Support;

use App\Modules\Ordering\Application\DTOs\TrashedDeliveryZone;
use App\Modules\Ordering\Application\Queries\AdminDeliveryZoneQuery;
use App\Modules\Shared\Presentation\Support\TrashItem;
use App\Modules\Shared\Presentation\Support\TrashKind;
use App\Modules\Shared\Presentation\Support\TrashSource;

/**
 * What the Ordering module adds to the Trash. Orders are never deleted.
 */
final class DeliveryZoneTrash implements TrashSource
{
    private const KIND = 'delivery_zone';

    public function __construct(private readonly AdminDeliveryZoneQuery $zones) {}

    public function kinds(): array
    {
        return [
            new TrashKind(self::KIND, 'Delivery zones', 'Delivery zone', 'admin.delivery-zones', 'The delivery zone is deleted for good. Old orders keep their own copy of its name and fee.', 'manage-sales'),
        ];
    }

    public function items(): array
    {
        return array_map(
            fn (TrashedDeliveryZone $zone): TrashItem => new TrashItem(self::KIND, $zone->id, $zone->name, $zone->deletedAt),
            $this->zones->trashed(),
        );
    }
}
