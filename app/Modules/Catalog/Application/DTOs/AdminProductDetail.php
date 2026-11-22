<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

use App\Modules\Catalog\Domain\Enums\LeadTimeClass;
use App\Modules\Catalog\Domain\Enums\ProductType;

/**
 * Everything the product edit form and the image manager show.
 */
final readonly class AdminProductDetail
{
    /**
     * @param  list<AdminVariantRow>  $variants  in sort order
     * @param  list<ProductImageView>  $images  in sort order
     * @param  list<int>  $tagIds  also tags that are in the Trash (the product keeps them)
     */
    public function __construct(
        public int $id,
        public ProductType $type,
        public int $categoryId,
        public string $name,
        public string $slug,
        public ?string $shortDescription,
        public ?string $description,
        public LeadTimeClass $leadTimeClass,
        public int $leadTimeMinutes,
        public bool $isNew,
        public bool $isHot,
        public bool $isPublished,
        public bool $isAvailable,
        public array $variants,
        public array $images,
        public array $tagIds,
    ) {}
}
