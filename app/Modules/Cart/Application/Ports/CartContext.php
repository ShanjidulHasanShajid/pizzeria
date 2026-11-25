<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\Ports;

use App\Modules\Cart\Domain\ValueObjects\CartOwner;

/**
 * Whose cart are we working on? The answer never comes from a form field or an address,
 * so nobody can ask for the cart of somebody else.
 */
interface CartContext
{
    /** The logged-in customer, otherwise the guest, or null for a visitor who never had a cart. */
    public function current(): ?CartOwner;

    /** Like current(), but a visitor without a token gets one. Only for adding something. */
    public function currentOrNew(): CartOwner;

    /** The guest of this browser, also right after logging in. Null when there is none. */
    public function guest(): ?CartOwner;

    /** The guest cart was changed just now: its days start again. Does nothing for a customer. */
    public function renewGuest(): void;

    /** The guest cart was taken over by the customer: forget its token. */
    public function forgetGuest(): void;
}
