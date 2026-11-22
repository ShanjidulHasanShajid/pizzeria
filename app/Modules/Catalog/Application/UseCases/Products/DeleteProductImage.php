<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Application\DTOs\ProductImageInput;
use App\Modules\Catalog\Application\Services\CatalogImages;
use App\Modules\Catalog\Domain\Exceptions\InvalidProductImages;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;

/**
 * Removes one image for good (images have no Trash). If it was the main image, the next
 * one becomes main. The row goes first, then the two files.
 */
final class DeleteProductImage
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly CatalogImages $images,
    ) {}

    public function handle(ProductImageInput $input): void
    {
        $product = $this->products->find($input->productId)
            ?? throw EntityNotFound::withId('Product', $input->productId);

        try {
            $removed = $product->removeImage($input->originalPath);
        } catch (InvalidProductImages $rule) {
            throw ValidationFailed::forField('uploads', $rule->getMessage());
        }

        $this->products->save($product);
        $this->images->delete($removed);
    }
}
