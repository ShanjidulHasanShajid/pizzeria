<?php

declare(strict_types=1);

use App\Modules\Cart\Domain\Entities\Cart;
use App\Modules\Cart\Domain\Entities\CartLine;
use App\Modules\Cart\Domain\Repositories\CartRepository;
use App\Modules\Cart\Domain\ValueObjects\CartOwner;
use App\Modules\Cart\Domain\ValueObjects\ChosenOptions;
use App\Modules\Cart\Domain\ValueObjects\KitchenNote;
use App\Modules\Cart\Infrastructure\Persistence\Models\Cart as CartModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Shared\Application\Ports\Clock;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\Quantity;
use App\Modules\Shared\Infrastructure\Adapters\FrozenClock;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;

/*
| Everything here works with real requests and a real cookie. The guest has 2 Cola at ৳60.
*/
beforeEach(function (): void {
    $this->cola = ProductModel::factory()->for(CategoryModel::factory()->create())
        ->has(ProductVariantModel::factory()->asDefault()->state(['label' => 'Can', 'price' => 6_000]), 'variants')
        ->create(['name' => 'Cola', 'slug' => 'cola']);
    $this->token = str_repeat('a', 40);
    $this->guest = CartOwner::guest($this->token);
    $this->carts = app(CartRepository::class);

    $this->give = function (CartOwner $owner, int $quantity): void {
        $this->carts->createIfMissing($owner);
        $cart = $this->carts->find($owner);
        $cart->addLine(CartLine::standard(
            $this->cola->id,
            $this->cola->variants()->firstOrFail()->id,
            ChosenOptions::none(),
            KitchenNote::none(),
            Quantity::of($quantity),
            Money::fromPoisha(6_000),
        ));
        $this->carts->save($cart);
    };
    $this->quantityOf = fn (CartOwner $owner): ?int => $this->carts->find($owner)?->lines()[0]->quantity()->value();
    ($this->give)($this->guest, 2);
});

describe('cookie', function (): void {
    it('brings the cart back on every page', function (): void {
        $this->withCookie('cart_token', $this->token);

        $this->get('/menu')->assertSee('aria-label="Cart, 2 items"', false);
        $this->get('/cart')->assertSee('Cola')->assertSee('Proceed to checkout');
    });

    it('shows nothing to a visitor without the cookie', function (): void {
        $this->get('/cart')->assertSee('Your cart is empty');
    });

    it('is worthless when somebody typed it instead of getting it from us', function (): void {
        $this->withUnencryptedCookie('cart_token', $this->token)->get('/cart')->assertSee('Your cart is empty');
    });

    it('is not sent to a visitor who only looks around', function (): void {
        $this->get('/menu')->assertCookieMissing('cart_token');
        $this->get('/cart')->assertCookieMissing('cart_token');
    });
});

describe('signing in', function (): void {
    beforeEach(function (): void {
        $this->withCookie('cart_token', $this->token);
        $this->user = User::factory()->create();
        $this->customer = CartOwner::customer($this->user->id);
        $this->login = fn (string $password = 'password') => $this->post('/login', ['email' => $this->user->email, 'password' => $password]);
    });

    it('takes the guest cart along and removes the cookie', function (): void {
        ($this->login)()->assertRedirect('/')->assertCookieExpired('cart_token');

        expect(($this->quantityOf)($this->customer))->toBe(2)
            ->and($this->carts->find($this->guest))->toBeNull();
        $this->get('/cart')->assertSee('Cola');
    });

    it('adds to what the customer had before', function (): void {
        ($this->give)($this->customer, 3);

        ($this->login)();

        expect(($this->quantityOf)($this->customer))->toBe(5)
            ->and(CartModel::query()->count())->toBe(1);
    });

    it('works with more than fits, up to the limit', function (): void {
        ($this->give)($this->customer, 19);

        ($this->login)()->assertRedirect('/');

        expect(($this->quantityOf)($this->customer))->toBe(20);
    });

    it('takes the guest cart along at registration', function (): void {
        $this->post('/register', [
            'name' => 'New Customer', 'email' => 'new@example.com', 'phone' => '01712345678',
            'password' => 'password1', 'password_confirmation' => 'password1',
        ])->assertRedirect();
        $user = User::query()->where('email', 'new@example.com')->firstOrFail();

        expect(($this->quantityOf)(CartOwner::customer($user->id)))->toBe(2);
    });

    it('leaves the guest cart alone when the password is wrong', function (): void {
        ($this->login)('wrong')->assertSessionHasErrors('email');

        expect(($this->quantityOf)($this->guest))->toBe(2)
            ->and($this->carts->find($this->customer))->toBeNull();
    });

    it('leaves the guest cart alone when the account is blocked', function (): void {
        $this->user->is_blocked = true;
        $this->user->save();

        ($this->login)()->assertSessionHasErrors('email');

        expect(($this->quantityOf)($this->guest))->toBe(2)
            ->and($this->carts->find($this->customer))->toBeNull();
    });

    it('creates no cart for a customer without any', function (): void {
        $this->carts->delete($this->carts->find($this->guest));

        ($this->login)()->assertRedirect('/');

        expect(CartModel::query()->count())->toBe(0);
    });

    it('keeps the cart for the next sign-in and hides it after signing out', function (): void {
        ($this->login)();
        $this->post('/logout');
        $this->get('/cart')->assertSee('Your cart is empty');

        ($this->login)();
        $this->get('/cart')->assertSee('Cola');
        expect(($this->quantityOf)($this->customer))->toBe(2);
    });
});

describe('cleanup', function (): void {
    it('deletes guest carts that were not changed for 5 days, and nothing else', function (): void {
        app()->instance(Clock::class, FrozenClock::at('2026-11-25 10:00:00'));
        $fresh = CartOwner::guest(str_repeat('b', 40));
        $customer = CartOwner::customer(User::factory()->create()->id);
        ($this->give)($fresh, 1);
        ($this->give)($customer, 1);
        $age = fn (CartOwner $owner, string $changedAt) => CartModel::query()->whereKey($this->carts->find($owner)->id())->update(['updated_at' => $changedAt]);
        $age($this->guest, '2026-11-20 09:59:59');
        $age($fresh, '2026-11-20 10:00:01');
        $age($customer, '2026-01-01 10:00:00');

        $this->artisan('cart:delete-old-guest-carts')->expectsOutput('Old guest carts deleted: 1')->assertSuccessful();

        expect($this->carts->find($this->guest))->toBeNull()
            ->and($this->carts->find($fresh))->not->toBeNull()
            ->and($this->carts->find($customer))->not->toBeNull();
    });

    it('says so when there is nothing to delete', function (): void {
        $this->artisan('cart:delete-old-guest-carts')->expectsOutput('Old guest carts deleted: 0')->assertSuccessful();
    });

    it('runs every night', function (): void {
        $events = array_filter(
            app(Schedule::class)->events(),
            fn (ScheduledEvent $event): bool => str_contains((string) $event->command, 'cart:delete-old-guest-carts'),
        );

        expect($events)->toHaveCount(1)
            ->and(array_values($events)[0]->expression)->toBe('30 4 * * *');
    });

    it('promises the same number of days in its description as the rule', function (): void {
        expect(Artisan::all()['cart:delete-old-guest-carts']->getDescription())->toContain(Cart::GUEST_LIFETIME_DAYS.' days');
    });
});
