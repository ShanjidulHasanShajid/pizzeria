<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Categories;

use App\Modules\Catalog\Application\DTOs\ReorderInput;
use App\Modules\Catalog\Domain\Entities\Category;
use App\Modules\Catalog\Domain\Repositories\CategoryRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * Saves a new order for ONE group: the top-level categories, or the sub-categories
 * of one parent. Mixing groups is refused; moving to another parent is done in the edit form.
 */
final class ReorderCategories
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(ReorderInput $input): void
    {
        if (count(array_unique($input->ids)) !== count($input->ids)) {
            throw ValidationFailed::forField('ids', 'Each category may appear only once.');
        }

        $this->transactions->run(function () use ($input): void {
            /** @var list<Category> $group */
            $group = [];

            foreach ($input->ids as $id) {
                $group[] = $this->categories->find($id)
                    ?? throw EntityNotFound::withId('Category', $id);
            }

            $parentIds = array_unique(array_map(fn (Category $category): string => (string) $category->parentId(), $group));

            if (count($parentIds) > 1) {
                throw ValidationFailed::forField('ids', 'Only categories with the same parent can be reordered together.');
            }

            $sort = SortOrder::first();

            foreach ($group as $category) {
                $category->moveTo($sort);
                $this->categories->save($category);
                $sort = $sort->next();
            }
        });
    }
}
