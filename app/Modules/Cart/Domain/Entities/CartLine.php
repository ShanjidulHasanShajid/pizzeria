<?php

declare(strict_types=1);

namespace App\Modules\Cart\Domain\Entities;

use App\Modules\Cart\Domain\Enums\CartLineType;
use App\Modules\Cart\Domain\ValueObjects\ChosenOptions;
use App\Modules\Cart\Domain\ValueObjects\KitchenNote;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\Quantity;

/**
 * One line of a cart: what was chosen and how many. It belongs to the Cart aggregate, which
 * guards the rules that need more than one line.
 * lastUnitPrice is what the customer saw last. It is for the message "the price has changed"
 * and is never used to charge anything.
 */
final class CartLine
{
    private function __construct(
        private ?int $id,
        private CartLineType $type,
        private int $productId,
        private int $variantId,
        private ChosenOptions $options,
        private KitchenNote $note,
        private Quantity $quantity,
        private Money $lastUnitPrice,
    ) {}

    /** A new line for a normal product. It gets its id when it is saved. */
    public static function standard(
        int $productId,
        int $variantId,
        ChosenOptions $options,
        KitchenNote $note,
        Quantity $quantity,
        Money $unitPrice,
    ): self {
        return new self(null, CartLineType::Standard, $productId, $variantId, $options, $note, $quantity, $unitPrice);
    }

    /** Rebuilds a saved line. Only the mapper (Infrastructure) and tests call this. */
    public static function reconstitute(
        int $id,
        CartLineType $type,
        int $productId,
        int $variantId,
        ChosenOptions $options,
        KitchenNote $note,
        Quantity $quantity,
        Money $lastUnitPrice,
    ): self {
        return new self($id, $type, $productId, $variantId, $options, $note, $quantity, $lastUnitPrice);
    }

    /** The same line for another cart: without an id, so saving that cart inserts it. */
    public function asNewLine(): self
    {
        return new self(null, $this->type, $this->productId, $this->variantId, $this->options, $this->note, $this->quantity, $this->lastUnitPrice);
    }

    /** Same product, size, options and note, whatever the quantity. Such lines are merged. */
    public function isSameItemAs(self $other): bool
    {
        return $this->productId === $other->productId
            && $this->variantId === $other->variantId
            && $this->options->equals($other->options)
            && $this->note->equals($other->note);
    }

    public function changeQuantity(Quantity $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function changeNote(KitchenNote $note): void
    {
        $this->note = $note;
    }

    /** The customer has seen this price now. */
    public function rememberUnitPrice(Money $unitPrice): void
    {
        $this->lastUnitPrice = $unitPrice;
    }

    public function hasPriceChangedTo(Money $currentUnitPrice): bool
    {
        return ! $this->lastUnitPrice->equals($currentUnitPrice);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function type(): CartLineType
    {
        return $this->type;
    }

    public function productId(): int
    {
        return $this->productId;
    }

    public function variantId(): int
    {
        return $this->variantId;
    }

    public function options(): ChosenOptions
    {
        return $this->options;
    }

    public function note(): KitchenNote
    {
        return $this->note;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    public function lastUnitPrice(): Money
    {
        return $this->lastUnitPrice;
    }
}
