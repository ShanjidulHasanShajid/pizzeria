<?php

declare(strict_types=1);

namespace App\Modules\Cart\Infrastructure\Adapters;

use App\Modules\Cart\Application\DTOs\CartProduct;
use App\Modules\Cart\Application\Ports\CartProducts;
use App\Modules\Catalog\Application\DTOs\ProductSummary;
use App\Modules\Catalog\Application\Queries\ProductSummaryQuery;

/**
 * Gets names and images from the Catalog module, through its Application layer only.
 */
final class CatalogCartProducts implements CartProducts
{
    public function __construct(private readonly ProductSummaryQuery $summaries) {}

    public function byIds(array $ids): array
    {
        return array_map(fn (ProductSummary $product): CartProduct => new CartProduct(
            $product->id,
            $product->name,
            $product->isStandard,
            $product->isVisible ? $product->slug : null,
            $product->imagePath,
        ), $this->summaries->byIds($ids));
    }
}
