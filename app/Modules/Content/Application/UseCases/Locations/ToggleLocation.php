<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Locations;

use App\Modules\Content\Application\DTOs\LocationIdInput;
use App\Modules\Content\Domain\Repositories\LocationRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Switches a branch on or off. Returns true when it is on now.
 */
final class ToggleLocation
{
    public function __construct(private readonly LocationRepository $locations) {}

    public function handle(LocationIdInput $input): bool
    {
        $location = $this->locations->find($input->id) ?? throw EntityNotFound::withId('Location', $input->id);
        $toggled = $location->toggled();
        $this->locations->save($toggled);

        return $toggled->isActive;
    }
}
