<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

/**
 * What the customer chose, straight from the request: ids and a quantity, never a price.
 */
final readonly class QuoteLineInput
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
