<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\Ports;

use App\Modules\Cart\Application\DTOs\CartProduct;

/**
 * What the cart shows about products. An adapter gets it from the Catalog module.
 */
interface CartProducts
{
    /**
     * Also hidden and deleted products, so the cart can still name what is gone. Unknown ids are left out.
     *
     * @param  list<int>  $ids
     * @return array<int, CartProduct> product id => product
     */
    public function byIds(array $ids): array;
}
