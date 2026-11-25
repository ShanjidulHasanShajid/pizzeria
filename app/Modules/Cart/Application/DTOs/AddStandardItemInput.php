<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\DTOs;

/**
 * What the customer wants to add. There is no price in here on purpose: the server owns prices.
 */
final readonly class AddStandardItemInput
{
    /**
     * @param  array<int, list<int>>  $choices  option group id => chosen option ids
     */
    public function __construct(
        public int $productId,
        public int $variantId,
        public array $choices = [],
        public ?string $note = null,
        public int $quantity = 1,
    ) {}
}
