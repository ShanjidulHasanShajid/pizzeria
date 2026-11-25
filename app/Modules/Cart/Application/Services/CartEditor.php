<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\Services;

use App\Modules\Cart\Application\Ports\CartContext;
use App\Modules\Cart\Domain\Entities\Cart;
use App\Modules\Cart\Domain\Exceptions\CartRejected;
use App\Modules\Cart\Domain\Repositories\CartRepository;
use App\Modules\Cart\Domain\ValueObjects\CartOwner;
use App\Modules\Shared\Application\Ports\TransactionManager;

/**
 * The steps every change of a cart has in common: find out whose cart it is, lock it, load it,
 * change it, save it. Two requests at the same moment run one after the other.
 */
final class CartEditor
{
    public function __construct(
        private readonly CartContext $context,
        private readonly CartRepository $carts,
        private readonly TransactionManager $transactions,
    ) {}

    /**
     * Changes the cart of this visitor, which is created when there is none.
     *
     * @param  callable(Cart): void  $change
     */
    public function editOrCreate(callable $change): void
    {
        $owner = $this->context->currentOrNew();
        $this->carts->createIfMissing($owner);

        if (! $this->lockAndChange($owner, $change)) {
            throw CartRejected::changedMeanwhile();
        }
    }

    /**
     * Changes the cart of this visitor. Returns false when there is none.
     *
     * @param  callable(Cart): void  $change
     */
    public function edit(callable $change): bool
    {
        $owner = $this->context->current();

        return $owner !== null && $this->lockAndChange($owner, $change);
    }

    /**
     * @param  callable(Cart): void  $change
     */
    private function lockAndChange(CartOwner $owner, callable $change): bool
    {
        $wasChanged = $this->transactions->run(function () use ($owner, $change): bool {
            $this->carts->lock($owner);
            $cart = $this->carts->find($owner);

            if ($cart === null) {
                return false;
            }

            $change($cart);
            $this->carts->save($cart);

            return true;
        });

        if ($wasChanged) {
            $this->context->renewGuest();
        }

        return $wasChanged;
    }
}
