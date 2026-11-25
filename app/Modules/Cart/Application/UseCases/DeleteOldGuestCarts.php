<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\UseCases;

use App\Modules\Cart\Domain\Entities\Cart;
use App\Modules\Cart\Domain\Repositories\CartRepository;
use App\Modules\Shared\Application\Ports\Clock;

/**
 * Deletes the guest carts nobody can reach any more: their cookie ended (business rules section 7).
 * Carts of customers stay.
 */
final class DeleteOldGuestCarts
{
    public function __construct(
        private readonly CartRepository $carts,
        private readonly Clock $clock,
    ) {}

    /** Returns how many were deleted. */
    public function handle(): int
    {
        return $this->carts->deleteGuestCartsUnchangedSince(
            $this->clock->now()->modify(sprintf('-%d days', Cart::GUEST_LIFETIME_DAYS)),
        );
    }
}
