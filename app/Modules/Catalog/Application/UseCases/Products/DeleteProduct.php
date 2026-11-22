<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Moves a product to the Trash. Never blocked: old orders keep their own snapshot of
 * name and price (business rules section 3), and the shop answers 404 for it at once.
 */
final class DeleteProduct
{
    public function __construct(private readonly ProductRepository $products) {}

    public function handle(CatalogItemInput $input): void
    {
        if ($this->products->find($input->id) === null) {
            throw EntityNotFound::withId('Product', $input->id);
        }

        $this->products->delete($input->id);
    }
}
