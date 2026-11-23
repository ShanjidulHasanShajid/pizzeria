<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * A category with some of its product cards.
 */
final readonly class CategorySection
{
    /**
     * @param  list<ProductCard>  $products
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public array $products,
    ) {}
}
