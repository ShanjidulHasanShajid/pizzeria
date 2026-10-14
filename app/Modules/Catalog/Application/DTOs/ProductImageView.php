<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * One picture in the product page gallery. Paths are relative to the public disk.
 */
final readonly class ProductImageView
{
    public function __construct(
        public string $originalPath,
        public string $thumbnailPath,
        public bool $isMain,
    ) {}
}
