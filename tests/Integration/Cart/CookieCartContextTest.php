<?php

declare(strict_types=1);

use App\Modules\Cart\Application\Ports\CartContext;
use App\Modules\Cart\Domain\ValueObjects\CartOwner;
use App\Modules\Cart\Infrastructure\Adapters\CookieCartContext;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

beforeEach(function (): void {
    $this->context = app(CartContext::class);
    // The context of a request that brought this cookie.
    $this->contextWithCookie = function (mixed $value): CartContext {
        app()->instance('request', Request::create('/', 'GET', [], [CookieCartContext::COOKIE => $value]));

        return app(CartContext::class);
    };
});

it('knows nobody before something is added, and sets no cookie', function (): void {
    expect($this->context->current())->toBeNull()
        ->and($this->context->guest())->toBeNull()
        ->and(Cookie::hasQueued(CookieCartContext::COOKIE))->toBeFalse();
});

it('gives a guest one token and keeps it', function (): void {
    $first = $this->context->currentOrNew();

    expect($first->isGuest())->toBeTrue()
        ->and($this->context->currentOrNew()->equals($first))->toBeTrue()
        ->and($this->context->current()->equals($first))->toBeTrue();
});

it('sets a cookie for 5 days that scripts cannot read', function (): void {
    $this->travelTo('2026-11-25 10:00:00');
    $guest = $this->context->currentOrNew();
    $cookie = Cookie::queued(CookieCartContext::COOKIE);

    expect($cookie->getValue())->toBe($guest->guestToken())
        ->and($cookie->getExpiresTime())->toBe(now()->addDays(5)->getTimestamp())
        ->and($cookie->isHttpOnly())->toBeTrue();
});

it('starts the 5 days again and keeps the token', function (): void {
    $this->travelTo('2026-11-25 10:00:00');
    $guest = $this->context->currentOrNew();

    $this->travelTo('2026-11-28 10:00:00');
    $this->context->renewGuest();
    $cookie = Cookie::queued(CookieCartContext::COOKIE);

    expect($cookie->getValue())->toBe($guest->guestToken())
        ->and($cookie->getExpiresTime())->toBe(now()->addDays(5)->getTimestamp());
});

it('renews nothing for a visitor without a token', function (): void {
    $this->context->renewGuest();

    expect(Cookie::hasQueued(CookieCartContext::COOKIE))->toBeFalse();
});

it('recognises the guest by the cookie of the request', function (): void {
    $token = str_repeat('a', 40);

    expect(($this->contextWithCookie)($token)->current()->guestToken())->toBe($token);
});

it('ignores a strange cookie', function (mixed $strange): void {
    expect(($this->contextWithCookie)($strange)->current())->toBeNull();
})->with([
    'a list' => [['x']],
    'too short' => ['abc'],
    'empty' => [''],
]);

it('gives two visitors different tokens', function (): void {
    $first = $this->context->currentOrNew();
    Cookie::unqueue(CookieCartContext::COOKIE);

    expect($this->context->currentOrNew()->equals($first))->toBeFalse();
});

it('prefers the logged-in customer and still remembers the guest', function (): void {
    $guest = $this->context->currentOrNew();
    $user = User::factory()->create();
    $this->actingAs($user);

    expect($this->context->current()->equals(CartOwner::customer($user->id)))->toBeTrue()
        ->and($this->context->guest()->equals($guest))->toBeTrue();
});

it('gives a logged-in customer no token', function (): void {
    $this->actingAs(User::factory()->create());
    $this->context->currentOrNew();
    $this->context->renewGuest();

    expect($this->context->guest())->toBeNull()
        ->and(Cookie::hasQueued(CookieCartContext::COOKIE))->toBeFalse();
});

it('forgets the guest at once and removes the cookie', function (): void {
    $context = ($this->contextWithCookie)(str_repeat('a', 40));
    $context->forgetGuest();

    expect($context->guest())->toBeNull()
        ->and(Cookie::queued(CookieCartContext::COOKIE)->isCleared())->toBeTrue();
});
