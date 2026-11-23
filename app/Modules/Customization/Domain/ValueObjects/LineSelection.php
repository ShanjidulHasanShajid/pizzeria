<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Quantity;

/**
 * What the customer chose for one line: a product, one of its variants, the chosen option ids
 * per option group, and how many. Only ids: the browser never sends prices (business rules section 4).
 * The ids are sorted, so the order of the clicks never matters.
 */
final readonly class LineSelection
{
    /**
     * @param  array<int, list<int>>  $choices  option group id => chosen option ids
     */
    private function __construct(
        private int $productId,
        private int $variantId,
        private array $choices,
        private Quantity $quantity,
    ) {}

    /**
     * @param  array<int, array<int, int>>  $choices  option group id => chosen option ids
     */
    public static function of(int $productId, int $variantId, array $choices, Quantity $quantity): self
    {
        if ($productId < 1 || $variantId < 1) {
            throw InvalidValue::because('Choose a valid product and size.');
        }

        $clean = [];

        foreach ($choices as $optionGroupId => $optionIds) {
            $optionIds = array_values($optionIds);

            if ($optionGroupId < 1 || in_array(true, array_map(fn (int $id): bool => $id < 1, $optionIds), true)) {
                throw InvalidValue::because('Choose valid options.');
            }

            if (count(array_unique($optionIds)) !== count($optionIds)) {
                throw InvalidValue::because('The same option was chosen twice.');
            }

            if ($optionIds === []) {
                continue; // an empty group is the same as no picks
            }

            sort($optionIds);
            $clean[$optionGroupId] = $optionIds;
        }

        ksort($clean);

        return new self($productId, $variantId, $clean, $quantity);
    }

    public function productId(): int
    {
        return $this->productId;
    }

    public function variantId(): int
    {
        return $this->variantId;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    /**
     * @return list<int> the chosen option ids of this group, sorted; empty when nothing was chosen
     */
    public function optionIdsFor(int $optionGroupId): array
    {
        return $this->choices[$optionGroupId] ?? [];
    }

    /**
     * @return list<int> the groups with at least one pick, sorted
     */
    public function optionGroupIds(): array
    {
        return array_keys($this->choices);
    }

    /** Same product, size and picks, whatever the quantity. The cart (Phase 11) merges such lines. */
    public function isSameChoiceAs(self $other): bool
    {
        return $this->productId === $other->productId
            && $this->variantId === $other->variantId
            && $this->choices === $other->choices;
    }
}
