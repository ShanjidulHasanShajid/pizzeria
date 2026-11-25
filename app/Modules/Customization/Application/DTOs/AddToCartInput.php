<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

/**
 * A configured item on its way into the cart: ids, a quantity and a note, never a price.
 */
final readonly class AddToCartInput
{
    /**
     * @param  array<int, list<int>>  $choices  option group id => chosen option ids
     */
    public function __construct(
        public int $productId,
        public int $variantId,
        public array $choices,
        public int $quantity,
        public string $note,
    ) {}
}
