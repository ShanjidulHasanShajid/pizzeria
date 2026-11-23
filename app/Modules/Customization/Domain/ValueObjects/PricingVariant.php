<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\ValueObjects;

use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * The facts about one variant that the pricing engine needs. See PricingProduct.
 */
final readonly class PricingVariant
{
    public function __construct(
        private int $id,
        private string $label,
        private Money $price,
        private ?int $sizeTierId,
        private bool $isAvailable,
    ) {}

    public function id(): int
    {
        return $this->id;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function price(): Money
    {
        return $this->price;
    }

    /** Null for a variant that is not a size ("6 pcs"): per-size options are not offered then. */
    public function sizeTierId(): ?int
    {
        return $this->sizeTierId;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }
}
