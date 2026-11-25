<?php

declare(strict_types=1);

namespace Tests\Unit\Cart\Fakes;

use App\Modules\Cart\Application\DTOs\LineQuote;
use App\Modules\Cart\Application\DTOs\LineToPrice;
use App\Modules\Cart\Application\Ports\LinePricing;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\Quantity;

/**
 * Fixed prices per variant instead of the pricing engine. A variant without a price is sold out.
 */
final class FakeLinePricing implements LinePricing
{
    /** @var array<int, int> variant id => price in taka */
    public array $taka = [];

    /** How often it was asked. */
    public int $calls = 0;

    public function quote(array $lines): array
    {
        $this->calls++;

        return array_map(function (LineToPrice $line): LineQuote {
            if (! isset($this->taka[$line->variantId])) {
                return LineQuote::refused('Sold out right now.');
            }

            $price = Money::fromTaka($this->taka[$line->variantId]);

            return LineQuote::priced('Medium', [], $price, $price->multiply(Quantity::of($line->quantity)));
        }, $lines);
    }
}
