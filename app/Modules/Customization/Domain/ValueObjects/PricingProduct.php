<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\ValueObjects;

/**
 * The facts about one product that the pricing engine needs. The product itself belongs to the
 * Catalog module, and this module may not use Catalog's Domain (architecture section 5).
 * So the Application layer (next batch) builds this from Catalog's Application layer,
 * and the tests build it by hand.
 */
final readonly class PricingProduct
{
    /**
     * @param  list<PricingVariant>  $variants
     */
    public function __construct(
        private int $id,
        private string $name,
        private bool $isPublished,
        private bool $isAvailable,
        private array $variants,
    ) {}

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    /** The variant with this id, or null when the product has no such variant. */
    public function variant(int $variantId): ?PricingVariant
    {
        foreach ($this->variants as $variant) {
            if ($variant->id() === $variantId) {
                return $variant;
            }
        }

        return null;
    }
}
