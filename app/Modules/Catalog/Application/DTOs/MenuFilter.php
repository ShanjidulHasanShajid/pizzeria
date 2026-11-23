<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

use App\Modules\Catalog\Domain\Enums\ProductSort;
use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * What a customer wants to see on a listing page.
 */
final readonly class MenuFilter
{
    public const PER_PAGE = 12;

    /**
     * @param  ?int  $categoryId  of the page; products of its sub-categories count too
     * @param  ?string  $pageTag  slug of the tag page; always applied, not a choice of the customer
     * @param  list<string>  $tagSlugs  a product must have all of them
     * @param  ?Money  $minPrice  compared with the price on the card
     */
    public function __construct(
        public ?int $categoryId = null,
        public ?string $pageTag = null,
        public array $tagSlugs = [],
        public ?Money $minPrice = null,
        public ?Money $maxPrice = null,
        public bool $onlyAvailable = false,
        public ProductSort $sort = ProductSort::Recommended,
        public int $page = 1,
        public int $perPage = self::PER_PAGE,
    ) {}

    /** Did the customer choose anything? */
    public function isActive(): bool
    {
        return $this->tagSlugs !== []
            || $this->minPrice !== null
            || $this->maxPrice !== null
            || $this->onlyAvailable
            || $this->sort !== ProductSort::Recommended;
    }
}
