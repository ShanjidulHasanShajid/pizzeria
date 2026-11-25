<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\Ports;

use App\Modules\Customization\Domain\ValueObjects\PricingProduct;

/**
 * Where the pricing engine's product facts come from. This module says what it needs;
 * an adapter in Infrastructure gets it from the Catalog module.
 */
interface PricingProductSource
{
    /** Null when there is no such product, or it is deleted. */
    public function find(int $productId): ?PricingProduct;

    /**
     * Several at once. Unknown and deleted products are left out.
     *
     * @param  list<int>  $productIds
     * @return array<int, PricingProduct> product id => product
     */
    public function findMany(array $productIds): array;
}
