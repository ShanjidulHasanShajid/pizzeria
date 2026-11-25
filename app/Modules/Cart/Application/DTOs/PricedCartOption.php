<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\DTOs;

use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * One chosen option of a priced line.
 */
final readonly class PricedCartOption
{
    public function __construct(
        public string $groupTitle,
        public string $name,
        public Money $charged,
        public bool $isFreePick,
    ) {}
}
