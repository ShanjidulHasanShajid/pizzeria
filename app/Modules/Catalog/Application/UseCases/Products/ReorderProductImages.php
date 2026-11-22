<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Application\DTOs\ReorderProductImagesInput;
use App\Modules\Catalog\Domain\Exceptions\InvalidProductImages;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;

/**
 * Saves the order the admin dragged the images into. The main image stays the main image.
 */
final class ReorderProductImages
{
    public function __construct(private readonly ProductRepository $products) {}

    public function handle(ReorderProductImagesInput $input): void
    {
        $product = $this->products->find($input->productId)
            ?? throw EntityNotFound::withId('Product', $input->productId);

        try {
            $product->reorderImages($input->originalPaths);
        } catch (InvalidProductImages $rule) {
            throw ValidationFailed::forField('uploads', $rule->getMessage());
        }

        $this->products->save($product);
    }
}
