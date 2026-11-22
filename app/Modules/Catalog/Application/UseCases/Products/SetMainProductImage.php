<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Application\DTOs\ProductImageInput;
use App\Modules\Catalog\Domain\Exceptions\InvalidProductImages;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;

/**
 * Makes one image the main image: the one on the product card and first on the product page.
 */
final class SetMainProductImage
{
    public function __construct(private readonly ProductRepository $products) {}

    public function handle(ProductImageInput $input): void
    {
        $product = $this->products->find($input->productId)
            ?? throw EntityNotFound::withId('Product', $input->productId);

        try {
            $product->setMainImage($input->originalPath);
        } catch (InvalidProductImages $rule) {
            throw ValidationFailed::forField('uploads', $rule->getMessage());
        }

        $this->products->save($product);
    }
}
