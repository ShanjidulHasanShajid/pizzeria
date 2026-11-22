<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Categories;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Application\Services\CatalogImages;
use App\Modules\Catalog\Domain\Exceptions\CatalogItemInUse;
use App\Modules\Catalog\Domain\Repositories\CategoryRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * "Delete forever" (super admins only, checked by the route). Only for a category
 * that is already in the Trash. Removes the row for good, then its image files.
 */
final class ForceDeleteCategory
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly CatalogImages $images,
    ) {}

    public function handle(CatalogItemInput $input): void
    {
        $category = $this->categories->findTrashed($input->id)
            ?? throw EntityNotFound::withId('Category in the Trash', $input->id);

        if ($this->categories->isUsedIncludingTrash($input->id)) {
            throw CatalogItemInUse::categoryStillReferenced($category->name());
        }

        $this->categories->forceDelete($input->id);

        // Files last: if deleting the row had failed, the category would still need them.
        $image = $category->image();

        if ($image !== null) {
            $this->images->delete($image);
        }
    }
}
