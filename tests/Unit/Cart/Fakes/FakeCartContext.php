<?php

declare(strict_types=1);

namespace Tests\Unit\Cart\Fakes;

use App\Modules\Cart\Application\Ports\CartContext;
use App\Modules\Cart\Domain\ValueObjects\CartOwner;

/**
 * Says whose cart it is, without a cookie and without a login.
 */
final class FakeCartContext implements CartContext
{
    public int $renewals = 0;

    public function __construct(
        public ?CartOwner $customer = null,
        public ?CartOwner $guest = null,
    ) {}

    public function current(): ?CartOwner
    {
        return $this->customer ?? $this->guest;
    }

    public function currentOrNew(): CartOwner
    {
        return $this->current() ?? $this->guest = CartOwner::guest(str_repeat('n', CartOwner::TOKEN_LENGTH));
    }

    public function guest(): ?CartOwner
    {
        return $this->guest;
    }

    public function renewGuest(): void
    {
        $this->renewals++;
    }

    public function forgetGuest(): void
    {
        $this->guest = null;
    }
}
