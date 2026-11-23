<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Locations;

use App\Modules\Content\Application\DTOs\LocationIdInput;
use App\Modules\Content\Domain\Repositories\LocationRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Moves a branch to the Trash.
 */
final class DeleteLocation
{
    public function __construct(private readonly LocationRepository $locations) {}

    public function handle(LocationIdInput $input): void
    {
        if ($this->locations->find($input->id) === null) {
            throw EntityNotFound::withId('Location', $input->id);
        }

        $this->locations->delete($input->id);
    }
}
