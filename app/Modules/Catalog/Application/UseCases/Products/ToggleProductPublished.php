<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * The quick "Published" switch on the product list: publish or unpublish.
 * Returns the new state (true = published).
 */
final class ToggleProductPublished
{
    public function __construct(private readonly ProductRepository $products) {}

    public function handle(CatalogItemInput $input): bool
    {
        $product = $this->products->find($input->id)
            ?? throw EntityNotFound::withId('Product', $input->id);

        if ($product->isPublished()) {
            $product->unpublish();
        } else {
            $product->publish();
        }

        $this->products->save($product);

        return $product->isPublished();
    }
}
