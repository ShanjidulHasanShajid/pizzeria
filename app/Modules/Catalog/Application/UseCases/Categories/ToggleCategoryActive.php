<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Categories;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Repositories\CategoryRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * The quick on/off switch on the category list. An inactive category and its
 * products disappear from the shop. Returns the new state (true = active).
 */
final class ToggleCategoryActive
{
    public function __construct(private readonly CategoryRepository $categories) {}

    public function handle(CatalogItemInput $input): bool
    {
        $category = $this->categories->find($input->id)
            ?? throw EntityNotFound::withId('Category', $input->id);

        if ($category->isActive()) {
            $category->deactivate();
        } else {
            $category->activate();
        }

        $this->categories->save($category);

        return $category->isActive();
    }
}
