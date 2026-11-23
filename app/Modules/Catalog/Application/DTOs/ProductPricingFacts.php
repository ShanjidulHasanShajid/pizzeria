<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * One product, reduced to what pricing needs. Other modules use this instead of the Product entity.
 */
final readonly class ProductPricingFacts
{
    /**
     * @param  bool  $isVisible  customers can see it: published, in an active category
     * @param  bool  $isAvailable  not marked sold out (each variant has its own switch)
     * @param  list<VariantPricingFacts>  $variants  not deleted, in sort order
     */
    public function __construct(
        public int $productId,
        public string $name,
        public bool $isVisible,
        public bool $isAvailable,
        public array $variants,
    ) {}
}
