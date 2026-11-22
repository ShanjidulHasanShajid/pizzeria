<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Application\Services\CatalogImages;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * "Delete forever" (super admins only, checked by the route), only from the Trash.
 * The row goes with its variants, images and tag links; then the image files are deleted.
 */
final class ForceDeleteProduct
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly CatalogImages $images,
    ) {}

    public function handle(CatalogItemInput $input): void
    {
        $product = $this->products->findTrashed($input->id)
            ?? throw EntityNotFound::withId('Product in the Trash', $input->id);

        $this->products->forceDelete($input->id);

        // Files last: if deleting the row had failed, the product would still need them.
        foreach ($product->images() as $image) {
            $this->images->delete($image->path());
        }
    }
}
