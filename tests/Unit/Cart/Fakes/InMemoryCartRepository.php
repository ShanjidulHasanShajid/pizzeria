<?php

declare(strict_types=1);

namespace Tests\Unit\Cart\Fakes;

use App\Modules\Cart\Domain\Entities\Cart;
use App\Modules\Cart\Domain\Entities\CartLine;
use App\Modules\Cart\Domain\Repositories\CartRepository;
use App\Modules\Cart\Domain\ValueObjects\CartOwner;

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
        if ($this->find($owner) === null) {
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

    // Test helper (not part of the interface)

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
