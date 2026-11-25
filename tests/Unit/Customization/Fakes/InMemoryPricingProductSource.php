<?php

declare(strict_types=1);

namespace Tests\Unit\Customization\Fakes;

use App\Modules\Customization\Application\Ports\PricingProductSource;
use App\Modules\Customization\Domain\ValueObjects\PricingProduct;

/**
 * Product facts in an array, instead of the Catalog module.
 */
final class InMemoryPricingProductSource implements PricingProductSource
{
    /** @var array<int, PricingProduct> */
    private array $products = [];

    public function add(PricingProduct $product): void
    {
        $this->products[$product->id()] = $product;
    }

    public function find(int $productId): ?PricingProduct
    {
        return $this->products[$productId] ?? null;
    }

    public function findMany(array $productIds): array
    {
        return array_intersect_key($this->products, array_flip($productIds));
    }
}
