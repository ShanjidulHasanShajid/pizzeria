<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

final readonly class ReorderProductImagesInput
{
    /**
     * @param  list<string>  $originalPaths  every image of the product, in the new order
     */
    public function __construct(
        public int $productId,
        public array $originalPaths,
    ) {}
}
