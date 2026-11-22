<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Categories;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Exceptions\CatalogItemInUse;
use App\Modules\Catalog\Domain\Repositories\CategoryRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Moves a category to the Trash. Refused while it has products or sub-categories
 * (business rules section 3). Its image files stay: a restored category needs them.
 */
final class DeleteCategory
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(CatalogItemInput $input): void
    {
        $this->transactions->run(function () use ($input): void {
            $category = $this->categories->find($input->id)
                ?? throw EntityNotFound::withId('Category', $input->id);

            if ($this->categories->hasProducts($input->id)) {
                throw CatalogItemInUse::categoryWithProducts($category->name());
            }

            if ($this->categories->hasChildren($input->id)) {
                throw CatalogItemInUse::categoryWithSubCategories($category->name());
            }

            $this->categories->delete($input->id);
        });
    }
}
