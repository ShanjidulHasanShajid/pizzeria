<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

use App\Modules\Catalog\Domain\Enums\LeadTimeClass;
use App\Modules\Catalog\Domain\Enums\ProductType;
use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * One product card on a menu, category, home or search page (website plan 4.5).
 */
final readonly class ProductCard
{
    /**
     * @param  Money  $fromPrice  the cheapest available variant; when none is available, the cheapest variant
     * @param  bool  $hasSeveralPrices  true shows "from ৳X", false shows the exact price
     * @param  list<string>  $variantLabels  size or portion chips, in sort order
     * @param  ?string  $imagePath  thumbnail of the main image, relative to the public disk
     * @param  list<TagBadge>  $tags
     * @param  bool  $isAvailable  false shows the disabled "Sold out" state
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public ?string $shortDescription,
        public ProductType $type,
        public Money $fromPrice,
        public bool $hasSeveralPrices,
        public array $variantLabels,
        public ?string $imagePath,
        public array $tags,
        public bool $isNew,
        public bool $isHot,
        public string $leadTimeLabel,
        public LeadTimeClass $leadTimeClass,
        public bool $isAvailable,
    ) {}
}
