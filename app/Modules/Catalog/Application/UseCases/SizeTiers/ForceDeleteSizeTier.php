<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\SizeTiers;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Exceptions\CatalogItemInUse;
use App\Modules\Catalog\Domain\Repositories\SizeTierRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * "Delete forever" (super admins only, checked by the route). Only for a size that is
 * already in the Trash and that no variant uses, also removed ones. Phase 8 adds option prices.
 */
final class ForceDeleteSizeTier
{
    public function __construct(private readonly SizeTierRepository $sizeTiers) {}

    public function handle(CatalogItemInput $input): void
    {
        $sizeTier = $this->sizeTiers->findTrashed($input->id)
            ?? throw EntityNotFound::withId('Size tier in the Trash', $input->id);

        if ($this->sizeTiers->isUsedIncludingTrash($input->id)) {
            throw CatalogItemInUse::sizeTierStillReferenced($sizeTier->name());
        }

        $this->sizeTiers->forceDelete($input->id);
    }
}
