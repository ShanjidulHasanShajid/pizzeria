<?php

declare(strict_types=1);

use App\Modules\Cart\Application\Ports\CartContext;
use App\Modules\Cart\Domain\ValueObjects\CartOwner;
use App\Modules\Cart\Infrastructure\Adapters\SessionCartContext;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;

beforeEach(function (): void {
    $this->context = app(CartContext::class);
});

it('knows nobody before something is added', function (): void {
    expect($this->context->current())->toBeNull()
        ->and($this->context->guest())->toBeNull()
        ->and(session()->has(SessionCartContext::SESSION_KEY))->toBeFalse();
});

it('gives a guest one token and keeps it', function (): void {
    $first = $this->context->currentOrNew();

    expect($first->isGuest())->toBeTrue()
        ->and($this->context->currentOrNew()->equals($first))->toBeTrue()
        ->and($this->context->current()->equals($first))->toBeTrue();
});

it('gives two visitors different tokens', function (): void {
    $first = $this->context->currentOrNew();
    session()->flush();

    expect($this->context->currentOrNew()->equals($first))->toBeFalse();
});

it('prefers the logged-in customer and still remembers the guest', function (): void {
    $guest = $this->context->currentOrNew();
    $user = User::factory()->create();
    $this->actingAs($user);

    expect($this->context->current()->equals(CartOwner::customer($user->id)))->toBeTrue()
        ->and($this->context->guest()->equals($guest))->toBeTrue();
});

it('gives a logged-in customer no guest token', function (): void {
    $this->actingAs(User::factory()->create());
    $this->context->currentOrNew();

    expect($this->context->guest())->toBeNull();
});

it('forgets the guest', function (): void {
    $this->context->currentOrNew();
    $this->context->forgetGuest();

    expect($this->context->guest())->toBeNull();
});

it('ignores something strange in the session', function (mixed $strange): void {
    session()->put(SessionCartContext::SESSION_KEY, $strange);

    expect($this->context->guest())->toBeNull();
})->with([
    'a list' => [['x']],
    'a number' => [42],
    'too short' => ['abc'],
]);
