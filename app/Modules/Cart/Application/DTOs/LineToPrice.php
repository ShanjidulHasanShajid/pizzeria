<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\DTOs;

/**
 * One line to be priced: only ids and a quantity.
 */
final readonly class LineToPrice
{
    /**
     * @param  array<int, list<int>>  $choices  option group id => chosen option ids
     */
    public function __construct(
        public int $productId,
        public int $variantId,
        public array $choices,
        public int $quantity,
    ) {}
}
