<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\KitchenStations;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Exceptions\CatalogItemInUse;
use App\Modules\Catalog\Domain\Repositories\KitchenStationRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * "Delete forever" (super admins only, checked by the route). Only for a station that is
 * already in the Trash and that no category uses any more, not even one in the Trash.
 */
final class ForceDeleteKitchenStation
{
    public function __construct(private readonly KitchenStationRepository $stations) {}

    public function handle(CatalogItemInput $input): void
    {
        $station = $this->stations->findTrashed($input->id)
            ?? throw EntityNotFound::withId('Kitchen station in the Trash', $input->id);

        if ($this->stations->isUsedIncludingTrash($input->id)) {
            throw CatalogItemInUse::kitchenStationStillReferenced($station->name());
        }

        $this->stations->forceDelete($input->id);
    }
}
