<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Queries;

use App\Modules\Catalog\Application\DTOs\VariantPricingFacts;
use App\Modules\Catalog\Application\Queries\StorefrontCatalogQuery;
use App\Modules\Customization\Application\DTOs\ConfigurableVariant;
use App\Modules\Customization\Application\DTOs\ProductConfiguration;
use App\Modules\Customization\Application\Queries\ProductConfigurationQuery;
use App\Modules\Customization\Application\Queries\ProductOptionsQuery;

/**
 * Sizes from the Catalog module, option groups from this one.
 */
final class CatalogProductConfigurationQuery implements ProductConfigurationQuery
{
    public function __construct(
        private readonly StorefrontCatalogQuery $catalog,
        private readonly ProductOptionsQuery $options,
    ) {}

    public function forProduct(int $productId): ?ProductConfiguration
    {
        $facts = $this->catalog->pricingFacts($productId);

        if ($facts === null || ! $facts->isVisible) {
            return null;
        }

        return new ProductConfiguration(
            $productId,
            array_map(fn (VariantPricingFacts $variant): ConfigurableVariant => new ConfigurableVariant(
                $variant->id,
                $variant->label,
                $variant->price,
                $variant->sizeTierId,
                $variant->isAvailable,
            ), $facts->variants),
            $this->options->forProduct($productId),
        );
    }
}
