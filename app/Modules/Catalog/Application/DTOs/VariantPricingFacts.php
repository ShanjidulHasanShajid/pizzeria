<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * One variant, reduced to what pricing needs.
 */
final readonly class VariantPricingFacts
{
    public function __construct(
        public int $id,
        public string $label,
        public Money $price,
        public ?int $sizeTierId,
        public bool $isAvailable,
    ) {}
}
