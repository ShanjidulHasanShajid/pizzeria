<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

use App\Modules\Catalog\Domain\Enums\LeadTimeClass;
use App\Modules\Catalog\Domain\Enums\ProductType;

/**
 * Everything the product page shows (website plan 4.6).
 * Option groups belong to the Customization module (ProductConfigurationQuery).
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

    /**
     * The size that is chosen when the page opens: the default one, or when that is sold out the
     * first one that can be ordered. A visible product always has a size.
     */
    public function startVariant(): VariantView
    {
        $default = $this->variants[0];
        $firstAvailable = null;

        foreach ($this->variants as $variant) {
            if ($variant->isDefault) {
                $default = $variant;
            }

            if ($variant->isAvailable) {
                $firstAvailable ??= $variant;
            }
        }

        return $default->isAvailable ? $default : ($firstAvailable ?? $default);
    }
}
