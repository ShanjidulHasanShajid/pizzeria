<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Exceptions\RestoreBlocked;
use App\Modules\Catalog\Domain\Repositories\CategoryRepository;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Brings a product back from the Trash with its variants, images and tags.
 * Refused while its category is still in the Trash: restore the category first.
 */
final class RestoreProduct
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly CategoryRepository $categories,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(CatalogItemInput $input): void
    {
        $this->transactions->run(function () use ($input): void {
            $product = $this->products->findTrashed($input->id)
                ?? throw EntityNotFound::withId('Product in the Trash', $input->id);

            if ($this->categories->find($product->categoryId()) === null) {
                $categoryName = $this->categories->findTrashed($product->categoryId())?->name() ?? 'its category';

                throw RestoreBlocked::categoryInTrash($product->name(), $categoryName);
            }

            $this->products->restore($input->id);
        });
    }
}
