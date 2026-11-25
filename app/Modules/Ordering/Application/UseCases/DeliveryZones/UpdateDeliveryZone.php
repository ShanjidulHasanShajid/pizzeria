<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\UseCases\DeliveryZones;

use App\Modules\Ordering\Application\DTOs\SaveDeliveryZoneInput;
use App\Modules\Ordering\Application\Services\DeliveryZoneInputReader;
use App\Modules\Ordering\Domain\Repositories\DeliveryZoneRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Changes a zone. Orders that are already placed keep the fee they were placed with.
 */
final class UpdateDeliveryZone
{
    public function __construct(
        private readonly DeliveryZoneRepository $zones,
        private readonly DeliveryZoneInputReader $reader,
    ) {}

    public function handle(SaveDeliveryZoneInput $input): void
    {
        $zone = $this->zones->find((int) $input->id)
            ?? throw EntityNotFound::withId('Delivery zone', (int) $input->id);

        $zone->change($this->reader->read($input), $input->isActive);
        $this->zones->save($zone);
    }
}
