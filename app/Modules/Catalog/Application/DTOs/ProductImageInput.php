<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * One image of one product, named by its stored original path (how the Product aggregate knows it).
 */
final readonly class ProductImageInput
{
    public function __construct(
        public int $productId,
        public string $originalPath,
    ) {}
}
