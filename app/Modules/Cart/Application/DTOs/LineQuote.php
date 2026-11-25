<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\DTOs;

use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * The answer of the pricing engine: either the price with its parts, or why there is none.
 */
final readonly class LineQuote
{
    /**
     * @param  list<PricedCartOption>  $options
     * @param  ?string  $problem  for the customer; null when the line can be ordered
     */
    private function __construct(
        public ?string $variantLabel,
        public array $options,
        public ?Money $unitPrice,
        public ?Money $lineTotal,
        public ?string $problem,
    ) {}

    /**
     * @param  list<PricedCartOption>  $options
     */
    public static function priced(string $variantLabel, array $options, Money $unitPrice, Money $lineTotal): self
    {
        return new self($variantLabel, $options, $unitPrice, $lineTotal, null);
    }

    public static function refused(string $problem): self
    {
        return new self(null, [], null, null, $problem);
    }
}
