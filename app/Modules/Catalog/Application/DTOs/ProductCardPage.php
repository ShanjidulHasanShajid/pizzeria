<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * One page of product cards.
 */
final readonly class ProductCardPage
{
    /**
     * @param  list<ProductCard>  $items
     * @param  int  $page  the page that is shown; never higher than the last one
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}
}
