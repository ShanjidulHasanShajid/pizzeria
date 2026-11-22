<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Categories;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Repositories\CategoryRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * The "featured" star on the category list (featured categories get a tile on the
 * home page in Phase 10). Returns the new state (true = featured).
 */
final class ToggleCategoryFeatured
{
    public function __construct(private readonly CategoryRepository $categories) {}

    public function handle(CatalogItemInput $input): bool
    {
        $category = $this->categories->find($input->id)
            ?? throw EntityNotFound::withId('Category', $input->id);

        if ($category->isFeatured()) {
            $category->unfeature();
        } else {
            $category->feature();
        }

        $this->categories->save($category);

        return $category->isFeatured();
    }
}
