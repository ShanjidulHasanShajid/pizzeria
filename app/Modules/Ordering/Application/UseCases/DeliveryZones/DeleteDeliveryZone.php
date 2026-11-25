<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\UseCases\DeliveryZones;

use App\Modules\Ordering\Application\DTOs\DeliveryZoneIdInput;
use App\Modules\Ordering\Domain\Repositories\DeliveryZoneRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Moves a zone to the Trash.
 */
final class DeleteDeliveryZone
{
    public function __construct(private readonly DeliveryZoneRepository $zones) {}

    public function handle(DeliveryZoneIdInput $input): void
    {
        if ($this->zones->find($input->id) === null) {
            throw EntityNotFound::withId('Delivery zone', $input->id);
        }

        $this->zones->delete($input->id);
    }
}
