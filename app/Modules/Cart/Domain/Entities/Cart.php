<?php

declare(strict_types=1);

namespace App\Modules\Cart\Domain\Entities;

use App\Modules\Cart\Domain\Exceptions\CartRejected;
use App\Modules\Cart\Domain\ValueObjects\CartOwner;
use App\Modules\Cart\Domain\ValueObjects\KitchenNote;
use App\Modules\Shared\Domain\Entities\AggregateRoot;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\Quantity;

/**
 * The cart of one owner. It is the aggregate root of its lines: they change only through it,
 * so these rules always hold: no two lines for the same item, at most 20 of one item,
 * at most 30 lines (owner-decided 2026-11-25).
 */
final class Cart extends AggregateRoot
{
    public const MAX_LINES = 30;

    /** A guest cart lives this long after its last change (owner-decided 2026-11-25). */
    public const GUEST_LIFETIME_DAYS = 5;

    /**
     * @param  list<CartLine>  $lines
     */
    private function __construct(
        private readonly int $id,
        private readonly CartOwner $owner,
        private array $lines,
    ) {}

    /**
     * Rebuilds a saved cart. Only the mapper (Infrastructure) and tests call this.
     *
     * @param  list<CartLine>  $lines
     */
    public static function reconstitute(int $id, CartOwner $owner, array $lines): self
    {
        return new self($id, $owner, $lines);
    }

    /**
     * Adds the line, or raises the quantity when the same item is in the cart already.
     * The price of the new line was seen just now, so it becomes the last seen price.
     */
    public function addLine(CartLine $line): void
    {
        $twin = $this->sameItem($line);

        if ($twin !== null) {
            $twin->changeQuantity($this->sum($twin, $line));
            $twin->rememberUnitPrice($line->lastUnitPrice());

            return;
        }

        if (count($this->lines) >= self::MAX_LINES) {
            throw CartRejected::cartFull(self::MAX_LINES);
        }

        $this->lines[] = $line;
    }

    public function changeQuantity(int $lineId, Quantity $quantity): void
    {
        $this->line($lineId)->changeQuantity($quantity);
    }

    /**
     * A new note can make the line equal to another one. Then the two become one line.
     */
    public function changeNote(int $lineId, KitchenNote $note): void
    {
        $line = $this->line($lineId);
        $changed = clone $line;
        $changed->changeNote($note);
        $twin = $this->sameItem($changed, $line);

        if ($twin !== null) {
            $line->changeQuantity($this->sum($line, $twin));
            $this->drop($twin);
        }

        $line->changeNote($note);
    }

    /** The customer has seen this price of the line now. */
    public function rememberUnitPrice(int $lineId, Money $unitPrice): void
    {
        $this->line($lineId)->rememberUnitPrice($unitPrice);
    }

    public function removeLine(int $lineId): void
    {
        $this->drop($this->line($lineId));
    }

    public function clear(): void
    {
        $this->lines = [];
    }

    /**
     * Takes the lines of another cart (a guest logs in). Nothing is refused here: what does not
     * fit into the limits is left out. Returns how many items (not lines) were left out.
     */
    public function takeOver(self $other): int
    {
        $leftOut = 0;

        foreach ($other->lines as $line) {
            $twin = $this->sameItem($line);
            $wanted = $line->quantity()->value();

            if ($twin === null && count($this->lines) < self::MAX_LINES) {
                $this->lines[] = $line->asNewLine();

                continue;
            }

            $fits = $twin === null ? 0 : min($wanted, Quantity::MAX - $twin->quantity()->value());
            $leftOut += $wanted - $fits;

            if ($twin !== null && $fits > 0) {
                $twin->changeQuantity(Quantity::of($twin->quantity()->value() + $fits));
            }
        }

        return $leftOut;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function owner(): CartOwner
    {
        return $this->owner;
    }

    /**
     * @return list<CartLine> in the order they were added
     */
    public function lines(): array
    {
        return $this->lines;
    }

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    private function line(int $lineId): CartLine
    {
        foreach ($this->lines as $line) {
            if ($line->id() === $lineId) {
                return $line;
            }
        }

        throw CartRejected::lineNotFound();
    }

    private function sameItem(CartLine $line, ?CartLine $except = null): ?CartLine
    {
        foreach ($this->lines as $candidate) {
            if ($candidate !== $except && $candidate->isSameItemAs($line)) {
                return $candidate;
            }
        }

        return null;
    }

    private function sum(CartLine $one, CartLine $other): Quantity
    {
        $total = $one->quantity()->value() + $other->quantity()->value();

        if ($total > Quantity::MAX) {
            throw CartRejected::tooManyOfOneItem(Quantity::MAX);
        }

        return Quantity::of($total);
    }

    private function drop(CartLine $line): void
    {
        $this->lines = array_values(array_filter($this->lines, fn (CartLine $kept): bool => $kept !== $line));
    }
}
