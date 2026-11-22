<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Application\DTOs\UploadProductImagesInput;
use App\Modules\Catalog\Application\Services\CatalogImages;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Catalog\Domain\ValueObjects\ImagePath;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use Throwable;

/**
 * Adds one or more images at the end of the product's gallery (7.19). The first image
 * of a product becomes its main image. If saving fails, the new files are deleted again.
 */
final class UploadProductImages
{
    public const IMAGE_DIRECTORY = 'products';

    public const MAX_IMAGES = 10;

    public function __construct(
        private readonly ProductRepository $products,
        private readonly CatalogImages $images,
    ) {}

    public function handle(UploadProductImagesInput $input): void
    {
        $product = $this->products->find($input->productId)
            ?? throw EntityNotFound::withId('Product', $input->productId);

        if (count($product->images()) + count($input->images) > self::MAX_IMAGES) {
            throw ValidationFailed::forField('uploads', 'A product can have at most '.self::MAX_IMAGES.' images.');
        }

        /** @var list<ImagePath> $stored */
        $stored = [];

        try {
            foreach ($input->images as $upload) {
                $path = $this->images->store($upload, self::IMAGE_DIRECTORY);
                $stored[] = $path;
                $product->addImage($path);
            }

            $this->products->save($product);
        } catch (Throwable $failure) {
            foreach ($stored as $path) {
                $this->images->delete($path);
            }

            throw $failure;
        }
    }
}
