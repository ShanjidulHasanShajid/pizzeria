<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\DTOs;

use App\Modules\Cart\Domain\Enums\CartLineStatus;
use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * The whole cart with the prices of this moment.
 */
final readonly class PricedCart
{
    /**
     * @param  list<PricedCartLine>  $lines
     * @param  Money  $subtotal  of the lines that can be ordered
     */
    public function __construct(
        public array $lines,
        public Money $subtotal,
    ) {}

    public static function empty(): self
    {
        return new self([], Money::zero());
    }

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    /** How many items, not lines: 2 pizzas and 1 drink are 3. */
    public function itemCount(): int
    {
        return array_sum(array_map(fn (PricedCartLine $line): int => $line->quantity, $this->lines));
    }

    public function hasChangedPrices(): bool
    {
        foreach ($this->lines as $line) {
            if ($line->status === CartLineStatus::PriceChanged) {
                return true;
            }
        }

        return false;
    }

    public function canCheckOut(): bool
    {
        foreach ($this->lines as $line) {
            if ($line->status->blocksCheckout()) {
                return false;
            }
        }

        return ! $this->isEmpty();
    }
}
