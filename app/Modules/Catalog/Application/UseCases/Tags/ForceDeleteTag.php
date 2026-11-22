<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Tags;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Repositories\TagRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * "Delete forever" (super admins only, checked by the route), only from the Trash.
 * Products that had the tag simply lose it.
 */
final class ForceDeleteTag
{
    public function __construct(private readonly TagRepository $tags) {}

    public function handle(CatalogItemInput $input): void
    {
        $this->tags->findTrashed($input->id)
            ?? throw EntityNotFound::withId('Tag in the Trash', $input->id);

        $this->tags->forceDelete($input->id);
    }
}
