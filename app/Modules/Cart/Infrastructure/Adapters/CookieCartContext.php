<?php

declare(strict_types=1);

namespace App\Modules\Cart\Infrastructure\Adapters;

use App\Modules\Cart\Application\Ports\CartContext;
use App\Modules\Cart\Domain\Entities\Cart;
use App\Modules\Cart\Domain\ValueObjects\CartOwner;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The customer is the logged-in user; a guest is recognised by a random token in a cookie that
 * ends 5 days after the last change of the cart (owner-decided 2026-11-25).
 * Laravel encrypts the cookie, and scripts in the browser cannot read it (HttpOnly).
 */
final class CookieCartContext implements CartContext
{
    public const COOKIE = 'cart_token';

    private const MINUTES_PER_DAY = 24 * 60;

    public function __construct(
        private readonly Auth $auth,
        private readonly CookieJar $cookies,
        private readonly Request $request,
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
        // A cookie we set or removed in this request counts more than the one that came with it.
        $token = $this->cookies->hasQueued(self::COOKIE)
            ? $this->cookies->queued(self::COOKIE)?->getValue()
            : $this->request->cookie(self::COOKIE);

        if (! is_string($token)) {
            return null;
        }

        try {
            return CartOwner::guest($token);
        } catch (InvalidValue) {
            return null;
        }
    }

    public function renewGuest(): void
    {
        $guest = $this->customer() === null ? $this->guest() : null;

        if ($guest !== null) {
            $this->send((string) $guest->guestToken());
        }
    }

    public function forgetGuest(): void
    {
        $this->cookies->queue($this->cookies->forget(self::COOKIE));
    }

    private function customer(): ?CartOwner
    {
        $userId = $this->auth->guard()->id();

        return $userId === null ? null : CartOwner::customer((int) $userId);
    }

    private function newGuest(): CartOwner
    {
        $token = Str::random(CartOwner::TOKEN_LENGTH);
        $this->send($token);

        return CartOwner::guest($token);
    }

    private function send(string $token): void
    {
        $this->cookies->queue(self::COOKIE, $token, Cart::GUEST_LIFETIME_DAYS * self::MINUTES_PER_DAY);
    }
}
