<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Ports;

/**
 * Which products have something to choose (crust, toppings ...). The Customization module knows.
 */
interface ProductOptions
{
    /**
     * @param  list<int>  $productIds
     * @return list<int> those of them that have at least one active option group
     */
    public function idsWithOptions(array $productIds): array;
}
