<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Locations;

use App\Modules\Content\Application\DTOs\SaveLocationInput;
use App\Modules\Content\Application\Services\LocationInputReader;
use App\Modules\Content\Domain\Repositories\LocationRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

final class UpdateLocation
{
    public function __construct(
        private readonly LocationRepository $locations,
        private readonly LocationInputReader $reader,
    ) {}

    public function handle(SaveLocationInput $input): void
    {
        $id = (int) $input->id;
        $location = $this->locations->find($id) ?? throw EntityNotFound::withId('Location', $id);
        [$details, $hours] = $this->reader->read($input);

        $this->locations->save($location->change($details, $hours, $input->isActive));
    }
}
