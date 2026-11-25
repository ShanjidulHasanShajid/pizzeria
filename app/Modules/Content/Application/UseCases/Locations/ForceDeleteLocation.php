<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Locations;

use App\Modules\Content\Application\DTOs\LocationIdInput;
use App\Modules\Content\Application\Ports\LocationUsage;
use App\Modules\Content\Domain\Exceptions\LocationInUse;
use App\Modules\Content\Domain\Repositories\LocationRepository;

/**
 * Deletes a branch of the Trash for good. An id that is not in the Trash fails with "not found".
 * Refused while a delivery zone belongs to the branch (the database would refuse it too).
 */
final class ForceDeleteLocation
{
    public function __construct(
        private readonly LocationRepository $locations,
        private readonly LocationUsage $usage,
    ) {}

    public function handle(LocationIdInput $input): void
    {
        if ($this->usage->hasDeliveryZones($input->id)) {
            throw LocationInUse::byDeliveryZones();
        }

        $this->locations->forceDelete($input->id);
    }
}
