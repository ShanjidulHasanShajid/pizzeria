<?php

declare(strict_types=1);

namespace App\Modules\Cart\Infrastructure\Adapters;

use App\Modules\Cart\Application\Ports\CartContext;
use App\Modules\Cart\Domain\ValueObjects\CartOwner;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;

/**
 * The customer is the logged-in user; a guest is recognised by a random token in the session.
 * The session lives on the server, so the browser never sees the token.
 */
final class SessionCartContext implements CartContext
{
    public const SESSION_KEY = 'cart_token';

    public function __construct(
        private readonly Auth $auth,
        private readonly Session $session,
    ) {}

    public function current(): ?CartOwner
    {
        return $this->customer() ?? $this->guest();
    }

    public function currentOrNew(): CartOwner
    {
        return $this->current() ?? $this->newGuest();
    }

    public function guest(): ?CartOwner
    {
        $token = $this->session->get(self::SESSION_KEY);

        if (! is_string($token)) {
            return null;
        }

        try {
            return CartOwner::guest($token);
        } catch (InvalidValue) {
            return null;
        }
    }

    public function forgetGuest(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    private function customer(): ?CartOwner
    {
        $userId = $this->auth->guard()->id();

        return $userId === null ? null : CartOwner::customer((int) $userId);
    }

    private function newGuest(): CartOwner
    {
        $token = Str::random(CartOwner::TOKEN_LENGTH);
        $this->session->put(self::SESSION_KEY, $token);

        return CartOwner::guest($token);
    }
}
