<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Locations;

use App\Modules\Content\Application\DTOs\LocationIdInput;
use App\Modules\Content\Domain\Repositories\LocationRepository;

/**
 * Deletes a branch of the Trash for good. An id that is not in the Trash fails with "not found".
 */
final class ForceDeleteLocation
{
    public function __construct(private readonly LocationRepository $locations) {}

    public function handle(LocationIdInput $input): void
    {
        $this->locations->forceDelete($input->id);
    }
}
