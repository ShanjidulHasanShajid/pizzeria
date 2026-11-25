<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\UseCases\DeliveryZones;

use App\Modules\Ordering\Application\DTOs\SaveDeliveryZoneInput;
use App\Modules\Ordering\Application\Services\DeliveryZoneInputReader;
use App\Modules\Ordering\Domain\Entities\DeliveryZone;
use App\Modules\Ordering\Domain\Repositories\DeliveryZoneRepository;

/**
 * Adds a zone at the end of the list. Returns its id.
 */
final class CreateDeliveryZone
{
    public function __construct(
        private readonly DeliveryZoneRepository $zones,
        private readonly DeliveryZoneInputReader $reader,
    ) {}

    public function handle(SaveDeliveryZoneInput $input): int
    {
        $details = $this->reader->read($input);

        return $this->zones->save(DeliveryZone::create($details, $input->isActive, $this->zones->nextSortOrder()));
    }
}
