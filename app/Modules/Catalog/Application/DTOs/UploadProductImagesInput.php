<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

final readonly class UploadProductImagesInput
{
    /**
     * @param  list<ImageUpload>  $images  added at the end, in this order
     */
    public function __construct(
        public int $productId,
        public array $images,
    ) {}
}
