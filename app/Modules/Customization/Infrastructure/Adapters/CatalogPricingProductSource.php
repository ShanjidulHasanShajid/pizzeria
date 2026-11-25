<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Adapters;

use App\Modules\Catalog\Application\DTOs\ProductPricingFacts;
use App\Modules\Catalog\Application\DTOs\VariantPricingFacts;
use App\Modules\Catalog\Application\Queries\StorefrontCatalogQuery;
use App\Modules\Customization\Application\Ports\PricingProductSource;
use App\Modules\Customization\Domain\ValueObjects\PricingProduct;
use App\Modules\Customization\Domain\ValueObjects\PricingVariant;

/**
 * Gets the pricing facts from the Catalog module, through its Application layer only.
 */
final class CatalogPricingProductSource implements PricingProductSource
{
    public function __construct(private readonly StorefrontCatalogQuery $catalog) {}

    public function find(int $productId): ?PricingProduct
    {
        $facts = $this->catalog->pricingFacts($productId);

        return $facts === null ? null : $this->toProduct($facts);
    }

    public function findMany(array $productIds): array
    {
        return array_map($this->toProduct(...), $this->catalog->pricingFactsFor($productIds));
    }

    private function toProduct(ProductPricingFacts $facts): PricingProduct
    {
        return new PricingProduct(
            $facts->productId,
            $facts->name,
            $facts->isVisible,
            $facts->isAvailable,
            array_map(fn (VariantPricingFacts $variant): PricingVariant => new PricingVariant(
                $variant->id,
                $variant->label,
                $variant->price,
                $variant->sizeTierId,
                $variant->isAvailable,
            ), $facts->variants),
        );
    }
}
