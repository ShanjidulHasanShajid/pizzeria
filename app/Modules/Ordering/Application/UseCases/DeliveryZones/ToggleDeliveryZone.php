<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\UseCases\DeliveryZones;

use App\Modules\Ordering\Application\DTOs\DeliveryZoneIdInput;
use App\Modules\Ordering\Domain\Repositories\DeliveryZoneRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Switches a zone on or off. Returns the new state.
 */
final class ToggleDeliveryZone
{
    public function __construct(private readonly DeliveryZoneRepository $zones) {}

    public function handle(DeliveryZoneIdInput $input): bool
    {
        $zone = $this->zones->find($input->id)
            ?? throw EntityNotFound::withId('Delivery zone', $input->id);

        $zone->toggle();
        $this->zones->save($zone);

        return $zone->isActive();
    }
}
