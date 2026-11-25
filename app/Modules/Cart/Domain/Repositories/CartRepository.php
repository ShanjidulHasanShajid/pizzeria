<?php

declare(strict_types=1);

namespace App\Modules\Cart\Domain\Repositories;

use App\Modules\Cart\Domain\Entities\Cart;
use App\Modules\Cart\Domain\ValueObjects\CartOwner;
use DateTimeImmutable;

/**
 * The saved carts, one per owner. A cart is always loaded and saved as a whole, with its lines.
 * To change one: createIfMissing() if needed, then inside a transaction lock(), find(), save().
 */
interface CartRepository
{
    public function find(CartOwner $owner): ?Cart;

    /**
     * Makes sure the owner has a cart. Safe when two requests do it at the same moment.
     * Call it before the transaction.
     */
    public function createIfMissing(CartOwner $owner): void;

    /**
     * Other requests that want to change these carts wait until our transaction is over, so no
     * change gets lost. Call it first in the transaction, always in the same order of owners.
     */
    public function lock(CartOwner ...$owners): void;

    /** New lines are inserted, known ones updated, and lines that are gone are deleted. */
    public function save(Cart $cart): void;

    /** Deletes the cart with its lines for good. */
    public function delete(Cart $cart): void;

    /** Deletes guest carts whose last change was before this moment. Returns how many. */
    public function deleteGuestCartsUnchangedSince(DateTimeImmutable $moment): int;
}
