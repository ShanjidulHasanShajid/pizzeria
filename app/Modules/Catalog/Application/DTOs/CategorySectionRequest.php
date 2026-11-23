<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * "Show me up to $limit products of this category."
 */
final readonly class CategorySectionRequest
{
    /**
     * @param  list<int>  $pickedProductIds  shown first, in this order; the rest is filled up in sort order
     */
    public function __construct(
        public int $categoryId,
        public int $limit,
        public array $pickedProductIds = [],
    ) {}
}
