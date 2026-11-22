<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Categories;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Exceptions\RestoreBlocked;
use App\Modules\Catalog\Domain\Repositories\CategoryRepository;
use App\Modules\Catalog\Domain\Repositories\KitchenStationRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Brings a category back from the Trash, with its image. Refused while its parent category
 * or its kitchen station is still in the Trash: those must come back first.
 */
final class RestoreCategory
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly KitchenStationRepository $stations,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(CatalogItemInput $input): void
    {
        $this->transactions->run(function () use ($input): void {
            $category = $this->categories->findTrashed($input->id)
                ?? throw EntityNotFound::withId('Category in the Trash', $input->id);

            $parentId = $category->parentId();

            if ($parentId !== null && $this->categories->find($parentId) === null) {
                $parentName = $this->categories->findTrashed($parentId)?->name() ?? 'its parent';

                throw RestoreBlocked::parentCategoryInTrash($category->name(), $parentName);
            }

            if ($this->stations->find($category->kitchenStationId()) === null) {
                $stationName = $this->stations->findTrashed($category->kitchenStationId())?->name() ?? 'its station';

                throw RestoreBlocked::kitchenStationInTrash($category->name(), $stationName);
            }

            $this->categories->restore($input->id);
        });
    }
}
