<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Locations;

use App\Modules\Content\Application\DTOs\SaveLocationInput;
use App\Modules\Content\Application\Services\LocationInputReader;
use App\Modules\Content\Domain\Repositories\LocationRepository;
use App\Modules\Content\Domain\ValueObjects\Location;

/**
 * Adds a branch at the end of the list. Returns its id.
 */
final class CreateLocation
{
    public function __construct(
        private readonly LocationRepository $locations,
        private readonly LocationInputReader $reader,
    ) {}

    public function handle(SaveLocationInput $input): int
    {
        [$details, $hours] = $this->reader->read($input);

        return $this->locations->save(Location::create($details, $hours, $input->isActive, $this->locations->nextSortOrder()));
    }
}
