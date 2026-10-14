<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

use App\Modules\Catalog\Domain\Enums\LeadTimeClass;
use App\Modules\Catalog\Domain\Enums\ProductType;

/**
 * Everything the product page shows (website plan 4.6).
 * Option groups are added by the Customization module in Phase 8.
 */
final readonly class ProductDetail
{
    /**
     * @param  list<VariantView>  $variants  not deleted, in sort order; unavailable ones included and marked
     * @param  list<ProductImageView>  $images  in sort order
     * @param  list<TagBadge>  $tags
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public ProductType $type,
        public ?string $shortDescription,
        public ?string $description,
        public int $categoryId,
        public string $categoryName,
        public string $categorySlug,
        public string $leadTimeLabel,
        public LeadTimeClass $leadTimeClass,
        public bool $isNew,
        public bool $isHot,
        public bool $isGiftable,
        public bool $isAvailable,
        public array $variants,
        public array $images,
        public array $tags,
    ) {}
}
