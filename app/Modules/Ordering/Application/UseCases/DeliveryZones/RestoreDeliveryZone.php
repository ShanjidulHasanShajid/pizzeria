<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\UseCases\DeliveryZones;

use App\Modules\Ordering\Application\DTOs\DeliveryZoneIdInput;
use App\Modules\Ordering\Domain\Repositories\DeliveryZoneRepository;

/**
 * Brings a zone back from the Trash. An id that is not in the Trash fails with "not found".
 */
final class RestoreDeliveryZone
{
    public function __construct(private readonly DeliveryZoneRepository $zones) {}

    public function handle(DeliveryZoneIdInput $input): void
    {
        $this->zones->restore($input->id);
    }
}
