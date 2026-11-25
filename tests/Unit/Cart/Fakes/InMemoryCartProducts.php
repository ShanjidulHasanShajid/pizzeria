<?php

declare(strict_types=1);

namespace Tests\Unit\Cart\Fakes;

use App\Modules\Cart\Application\DTOs\CartProduct;
use App\Modules\Cart\Application\Ports\CartProducts;

final class InMemoryCartProducts implements CartProducts
{
    /** @var array<int, CartProduct> */
    private array $products = [];

    public function __construct(CartProduct ...$products)
    {
        foreach ($products as $product) {
            $this->products[$product->id] = $product;
        }
    }

    public function byIds(array $ids): array
    {
        return array_intersect_key($this->products, array_flip($ids));
    }
}
