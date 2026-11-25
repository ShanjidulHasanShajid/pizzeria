<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\Listeners;

use App\Modules\Cart\Application\DTOs\MergeGuestCartInput;
use App\Modules\Cart\Application\UseCases\MergeGuestCart;
use App\Modules\Identity\Application\Events\UserLoggedIn;
use App\Modules\Shared\Domain\Exceptions\DomainException;

/**
 * The guest cart joins the customer's cart when the guest signs in or registers (11.21).
 */
final class MergeCartOnLogin
{
    public function __construct(private readonly MergeGuestCart $mergeGuestCart) {}

    public function handle(UserLoggedIn $event): void
    {
        try {
            $this->mergeGuestCart->handle(new MergeGuestCartInput($event->userId));
        } catch (DomainException) {
            // Nobody may be kept from signing in because of a cart. The guest cart and its
            // cookie are still there, so the next sign-in tries again.
        }
    }
}
