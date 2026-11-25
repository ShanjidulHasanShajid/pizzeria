<?php

declare(strict_types=1);

namespace Tests\Unit\Cart\Fakes;

use App\Modules\Cart\Domain\Entities\Cart;
use App\Modules\Cart\Domain\Entities\CartLine;
use App\Modules\Cart\Domain\Repositories\CartRepository;
use App\Modules\Cart\Domain\ValueObjects\CartOwner;
use DateTimeImmutable;

/**
 * A CartRepository in an array, for use case tests. Copies in, copies out, and it gives
 * new lines an id, like the database does.
 */
final class InMemoryCartRepository implements CartRepository
{
    /** @var array<int, Cart> */
    private array $carts = [];

    private int $nextCartId = 1;

    private int $nextLineId = 1;

    /** @var array<int, DateTimeImmutable> cart id => last change, only for carts set with changedAt() */
    private array $changedAt = [];

    /** As if another request removed every new cart at once. */
    public bool $losesNewCarts = false;

    /** @var list<CartOwner> the owners in the order they were locked */
    public array $locked = [];

    public function find(CartOwner $owner): ?Cart
    {
        foreach ($this->carts as $cart) {
            if ($cart->owner()->equals($owner)) {
                return $this->copy($cart);
            }
        }

        return null;
    }

    public function createIfMissing(CartOwner $owner): void
    {
        if ($this->find($owner) === null && ! $this->losesNewCarts) {
            $id = $this->nextCartId++;
            $this->carts[$id] = Cart::reconstitute($id, $owner, []);
        }
    }

    public function lock(CartOwner ...$owners): void
    {
        array_push($this->locked, ...$owners);
    }

    public function save(Cart $cart): void
    {
        $this->carts[$cart->id()] = $this->copy($cart);
    }

    public function delete(Cart $cart): void
    {
        unset($this->carts[$cart->id()]);
    }

    public function deleteGuestCartsUnchangedSince(DateTimeImmutable $moment): int
    {
        $before = count($this->carts);
        $this->carts = array_filter($this->carts, fn (Cart $cart): bool => ! $cart->owner()->isGuest()
            || ! isset($this->changedAt[$cart->id()])
            || $this->changedAt[$cart->id()] >= $moment);

        return $before - count($this->carts);
    }

    // Test helper (not part of the interface)

    public function changedAt(CartOwner $owner, DateTimeImmutable $moment): void
    {
        $this->changedAt[(int) $this->find($owner)?->id()] = $moment;
    }

    public function count(): int
    {
        return count($this->carts);
    }

    private function copy(Cart $cart): Cart
    {
        return Cart::reconstitute($cart->id(), $cart->owner(), array_map(
            fn (CartLine $line): CartLine => CartLine::reconstitute(
                $line->id() ?? $this->nextLineId++,
                $line->type(),
                $line->productId(),
                $line->variantId(),
                $line->options(),
                $line->note(),
                $line->quantity(),
                $line->lastUnitPrice(),
            ),
            $cart->lines(),
        ));
    }
}
