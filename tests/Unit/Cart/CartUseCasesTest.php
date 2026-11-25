<?php

declare(strict_types=1);

use App\Modules\Cart\Application\DTOs\AddStandardItemInput;
use App\Modules\Cart\Application\DTOs\CartProduct;
use App\Modules\Cart\Application\DTOs\ChangeLineNoteInput;
use App\Modules\Cart\Application\DTOs\ChangeLineQuantityInput;
use App\Modules\Cart\Application\DTOs\MergeGuestCartInput;
use App\Modules\Cart\Application\DTOs\RemoveLineInput;
use App\Modules\Cart\Application\Listeners\MergeCartOnLogin;
use App\Modules\Cart\Application\Services\CartEditor;
use App\Modules\Cart\Application\Services\CartPricer;
use App\Modules\Cart\Application\UseCases\AcceptCurrentPrices;
use App\Modules\Cart\Application\UseCases\AddStandardItem;
use App\Modules\Cart\Application\UseCases\ChangeLineNote;
use App\Modules\Cart\Application\UseCases\ChangeLineQuantity;
use App\Modules\Cart\Application\UseCases\ClearCart;
use App\Modules\Cart\Application\UseCases\DeleteOldGuestCarts;
use App\Modules\Cart\Application\UseCases\GetPricedCart;
use App\Modules\Cart\Application\UseCases\MergeGuestCart;
use App\Modules\Cart\Application\UseCases\RemoveLine;
use App\Modules\Cart\Domain\Enums\CartLineStatus;
use App\Modules\Cart\Domain\Exceptions\CartRejected;
use App\Modules\Cart\Domain\ValueObjects\CartOwner;
use App\Modules\Identity\Application\Events\UserLoggedIn;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Infrastructure\Adapters\FrozenClock;
use Tests\Unit\Cart\Fakes\FakeCartContext;
use Tests\Unit\Cart\Fakes\FakeLinePricing;
use Tests\Unit\Cart\Fakes\InMemoryCartProducts;
use Tests\Unit\Cart\Fakes\InMemoryCartRepository;
use Tests\Unit\Identity\PassThroughTransactionManager;

/*
| Product 10 "Margherita": variant 101 ৳450, variant 102 ৳850. Product 11 "Meal deal" is a combo.
| A variant without a price in $this->pricing is sold out.
*/
beforeEach(function (): void {
    $this->carts = new InMemoryCartRepository;
    $this->context = new FakeCartContext;
    $this->pricing = new FakeLinePricing;
    $this->pricing->taka = [101 => 450, 102 => 850, 111 => 999];

    $transactions = new PassThroughTransactionManager;
    $products = new InMemoryCartProducts(
        new CartProduct(10, 'Margherita', true, 'margherita', 'products/m.webp'),
        new CartProduct(11, 'Meal deal', false, 'meal-deal', null),
    );
    $editor = new CartEditor($this->context, $this->carts, $transactions);

    $add = new AddStandardItem($products, $this->pricing, $editor);
    $this->add = fn (int $variantId = 101, int $quantity = 1, ?string $note = null, int $productId = 10) => $add
        ->handle(new AddStandardItemInput($productId, $variantId, [], $note, $quantity));
    $this->changeQuantity = new ChangeLineQuantity($editor);
    $this->changeNote = new ChangeLineNote($editor);
    $this->remove = new RemoveLine($editor);
    $this->clear = new ClearCart($editor);
    $pricer = new CartPricer($this->pricing, $products);
    $this->acceptPrices = new AcceptCurrentPrices($editor, $pricer);
    $this->cart = fn () => (new GetPricedCart($this->context, $this->carts, $pricer))->handle();
    $this->merge = new MergeGuestCart($this->context, $this->carts, $transactions);
});

describe('adding an item', function (): void {
    it('puts it into the cart with the price of the server', function (): void {
        ($this->add)(102, 2, 'less spicy');

        $cart = ($this->cart)();

        expect($cart->lines)->toHaveCount(1)
            ->and($cart->lines[0]->productName)->toBe('Margherita')
            ->and($cart->lines[0]->note)->toBe('less spicy')
            ->and($cart->lines[0]->unitPrice->poisha())->toBe(85_000)
            ->and($cart->lines[0]->status)->toBe(CartLineStatus::Ok)
            ->and($cart->subtotal->poisha())->toBe(170_000)
            ->and($cart->itemCount())->toBe(2);
    });

    it('has no place for a price, so a forged price has no way in', function (): void {
        $fields = array_map(fn (ReflectionProperty $field): string => $field->getName(), (new ReflectionClass(AddStandardItemInput::class))->getProperties());

        expect($fields)->toBe(['productId', 'variantId', 'choices', 'note', 'quantity']);
    });

    it('merges the same item', function (): void {
        ($this->add)();
        ($this->add)(quantity: 2);

        expect(($this->cart)()->lines)->toHaveCount(1)
            ->and(($this->cart)()->itemCount())->toBe(3);
    });

    it('gives a new visitor a token and one cart', function (): void {
        ($this->add)();
        ($this->add)(102);

        expect($this->context->guest)->not->toBeNull()
            ->and($this->carts->count())->toBe(1);
    });

    it('uses the cart of the customer who is logged in', function (): void {
        $this->context->customer = CartOwner::customer(7);
        ($this->add)();

        expect($this->carts->find(CartOwner::customer(7))->lines())->toHaveCount(1)
            ->and($this->context->guest)->toBeNull();
    });

    it('locks the cart before it changes it', function (): void {
        $this->context->customer = CartOwner::customer(7);
        ($this->add)();

        expect($this->carts->locked)->toHaveCount(1)
            ->and($this->carts->locked[0]->userId())->toBe(7);
    });

    it('refuses a sold-out size and creates no cart', function (): void {
        unset($this->pricing->taka[101]);

        expect(fn () => ($this->add)())->toThrow(CartRejected::class, 'Sold out');
        expect($this->carts->count())->toBe(0)
            ->and($this->context->guest)->toBeNull();
    });

    it('refuses a product that does not exist', function (): void {
        ($this->add)(productId: 99);
    })->throws(CartRejected::class, 'no longer on the menu');

    it('refuses a combo: it needs choices that this line cannot hold', function (): void {
        ($this->add)(111, productId: 11);
    })->throws(CartRejected::class, '"Meal deal" cannot be ordered like this');

    it('refuses a quantity outside 1 to 20', function (int $quantity): void {
        ($this->add)(quantity: $quantity);
    })->with([0, -1, 21])->throws(InvalidValue::class);

    it('refuses more than 20 of the same item in total', function (): void {
        ($this->add)(quantity: 15);
        ($this->add)(quantity: 6);
    })->throws(CartRejected::class, 'at most 20');

    it('refuses a note that is too long', function (): void {
        ($this->add)(note: str_repeat('a', 201));
    })->throws(InvalidValue::class);
});

describe('changing the cart', function (): void {
    beforeEach(function (): void {
        ($this->add)(101, 1);
        ($this->add)(102, 2);
        $this->lineId = ($this->cart)()->lines[0]->lineId;
    });

    it('changes the quantity', function (): void {
        $this->changeQuantity->handle(new ChangeLineQuantityInput($this->lineId, 5));

        expect(($this->cart)()->lines[0]->quantity)->toBe(5);
    });

    it('refuses a quantity outside 1 to 20', function (int $quantity): void {
        $this->changeQuantity->handle(new ChangeLineQuantityInput($this->lineId, $quantity));
    })->with([0, 21])->throws(InvalidValue::class);

    it('changes the note', function (): void {
        $this->changeNote->handle(new ChangeLineNoteInput($this->lineId, 'no onions'));

        expect(($this->cart)()->lines[0]->note)->toBe('no onions');
    });

    it('removes a line', function (): void {
        $this->remove->handle(new RemoveLineInput($this->lineId));

        expect(($this->cart)()->lines)->toHaveCount(1)
            ->and(($this->cart)()->subtotal->poisha())->toBe(170_000);
    });

    it('empties the cart', function (): void {
        $this->clear->handle();

        expect(($this->cart)()->isEmpty())->toBeTrue();
    });

    it('cannot touch the line of somebody else', function (): void {
        $mine = $this->context->guest;
        $this->context->customer = CartOwner::customer(7);
        ($this->add)();

        expect(fn () => $this->changeQuantity->handle(new ChangeLineQuantityInput($this->lineId, 9)))->toThrow(CartRejected::class);
        expect(fn () => $this->changeNote->handle(new ChangeLineNoteInput($this->lineId, 'mine')))->toThrow(CartRejected::class);
        expect(fn () => $this->remove->handle(new RemoveLineInput($this->lineId)))->toThrow(CartRejected::class);
        expect($this->carts->find($mine)->lines())->toHaveCount(2);
    });
});

describe('a visitor without a cart', function (): void {
    it('sees an empty cart and gets no token from looking', function (): void {
        expect(($this->cart)()->isEmpty())->toBeTrue()
            ->and(($this->cart)()->canCheckOut())->toBeFalse()
            ->and($this->context->guest)->toBeNull()
            ->and($this->carts->count())->toBe(0);
    });

    it('can change nothing', function (): void {
        $this->clear->handle();
        $this->acceptPrices->handle();

        expect(fn () => $this->remove->handle(new RemoveLineInput(1)))->toThrow(CartRejected::class, 'not in your cart');
        expect(fn () => $this->changeQuantity->handle(new ChangeLineQuantityInput(1, 2)))->toThrow(CartRejected::class);
        expect($this->carts->count())->toBe(0);
    });
});

describe('re-pricing', function (): void {
    beforeEach(function (): void {
        ($this->add)(101, 2);
        ($this->add)(102, 1);
    });

    it('marks a changed price and counts with the new one', function (): void {
        $this->pricing->taka[101] = 500;

        $cart = ($this->cart)();

        expect($cart->lines[0]->status)->toBe(CartLineStatus::PriceChanged)
            ->and($cart->lines[0]->lastUnitPrice->poisha())->toBe(45_000)
            ->and($cart->lines[0]->unitPrice->poisha())->toBe(50_000)
            ->and($cart->lines[1]->status)->toBe(CartLineStatus::Ok)
            ->and($cart->subtotal->poisha())->toBe(185_000)
            ->and($cart->hasChangedPrices())->toBeTrue()
            ->and($cart->canCheckOut())->toBeTrue();
    });

    it('still reports the change at the next look, because looking changes nothing', function (): void {
        $this->pricing->taka[101] = 500;
        ($this->cart)();

        expect(($this->cart)()->lines[0]->status)->toBe(CartLineStatus::PriceChanged);
    });

    it('marks an unavailable line, leaves it out of the subtotal and blocks the checkout', function (): void {
        unset($this->pricing->taka[101]);

        $cart = ($this->cart)();

        expect($cart->lines[0]->status)->toBe(CartLineStatus::Unavailable)
            ->and($cart->lines[0]->productName)->toBe('Margherita')
            ->and($cart->lines[0]->problem)->toBe('Sold out right now.')
            ->and($cart->lines[0]->unitPrice)->toBeNull()
            ->and($cart->lines[0]->lineTotal)->toBeNull()
            ->and($cart->subtotal->poisha())->toBe(85_000)
            ->and($cart->canCheckOut())->toBeFalse();
    });

    it('can be checked out again after the unavailable line is removed', function (): void {
        unset($this->pricing->taka[101]);
        $this->remove->handle(new RemoveLineInput(($this->cart)()->lines[0]->lineId));

        expect(($this->cart)()->canCheckOut())->toBeTrue();
    });

    it('ends the notice when the customer accepts the new price', function (): void {
        $this->pricing->taka[101] = 500;
        $this->acceptPrices->handle();

        $cart = ($this->cart)();

        expect($cart->lines[0]->status)->toBe(CartLineStatus::Ok)
            ->and($cart->lines[0]->lastUnitPrice->poisha())->toBe(50_000)
            ->and($cart->subtotal->poisha())->toBe(185_000);
    });

    it('keeps the last seen price of an unavailable line', function (): void {
        unset($this->pricing->taka[101]);
        $this->acceptPrices->handle();

        expect(($this->cart)()->lines[0]->status)->toBe(CartLineStatus::Unavailable)
            ->and(($this->cart)()->lines[0]->lastUnitPrice->poisha())->toBe(45_000);
    });

    it('asks for all prices at once', function (): void {
        $this->pricing->calls = 0;
        ($this->cart)();

        expect($this->pricing->calls)->toBe(1);
    });
});

describe('merging after login', function (): void {
    beforeEach(function (): void {
        $this->customer = CartOwner::customer(7);
        $this->login = fn (): int => $this->merge->handle(new MergeGuestCartInput(7));
    });

    it('moves the guest cart into a customer cart that does not exist yet', function (): void {
        ($this->add)(101, 2);

        expect(($this->login)())->toBe(0)
            ->and($this->carts->find($this->customer)->lines())->toHaveCount(1)
            ->and($this->carts->count())->toBe(1)
            ->and($this->context->guest)->toBeNull();
    });

    it('adds to what the customer has already', function (): void {
        $this->context->customer = $this->customer;
        ($this->add)(101, 2);
        $this->context->customer = null;
        ($this->add)(101, 1);
        ($this->add)(102, 1);

        ($this->login)();
        $lines = $this->carts->find($this->customer)->lines();

        expect($lines)->toHaveCount(2)
            ->and($lines[0]->quantity()->value())->toBe(3);
    });

    it('reports what did not fit', function (): void {
        $this->context->customer = $this->customer;
        ($this->add)(101, 18);
        $this->context->customer = null;
        ($this->add)(101, 5);

        expect(($this->login)())->toBe(3);
    });

    it('locks the guest cart first, then the customer cart', function (): void {
        ($this->add)();
        $this->carts->locked = [];
        ($this->login)();

        expect($this->carts->locked)->toHaveCount(2)
            ->and($this->carts->locked[0]->isGuest())->toBeTrue()
            ->and($this->carts->locked[1]->userId())->toBe(7);
    });

    it('does no harm when it runs twice', function (): void {
        ($this->add)(101, 2);
        $guest = $this->context->guest;
        ($this->login)();
        $this->context->guest = $guest; // a second login request with the old cookie
        ($this->login)();

        expect($this->carts->find($this->customer)->lines()[0]->quantity()->value())->toBe(2);
    });

    it('creates no customer cart for a visitor who never had one', function (): void {
        expect(($this->login)())->toBe(0)
            ->and($this->carts->count())->toBe(0);
    });

    it('happens when the user signs in', function (): void {
        ($this->add)(101, 2);

        (new MergeCartOnLogin($this->merge))->handle(new UserLoggedIn(7, new DateTimeImmutable));

        expect($this->carts->find($this->customer)->lines())->toHaveCount(1);
    });

    it('never keeps anybody from signing in, and loses nothing when it fails', function (): void {
        ($this->add)(101, 2);
        $guest = $this->context->guest;
        $this->carts->losesNewCarts = true;

        (new MergeCartOnLogin($this->merge))->handle(new UserLoggedIn(7, new DateTimeImmutable));

        expect($this->context->guest)->toBe($guest)
            ->and($this->carts->find($guest)->lines())->toHaveCount(1);
    });
});

describe('the days of a guest cart', function (): void {
    it('start again with every change', function (): void {
        ($this->add)();
        expect($this->context->renewals)->toBe(1);

        $this->changeQuantity->handle(new ChangeLineQuantityInput(($this->cart)()->lines[0]->lineId, 3));
        expect($this->context->renewals)->toBe(2);
    });

    it('do not start again by looking, or by a change that was refused', function (): void {
        ($this->add)();
        ($this->cart)();

        expect(fn () => $this->remove->handle(new RemoveLineInput(99)))->toThrow(CartRejected::class);
        expect($this->context->renewals)->toBe(1);
    });

    it('end after 5 days without a change, for guests only', function (): void {
        $clock = FrozenClock::at('2026-11-25 10:00:00');
        $old = CartOwner::guest(str_repeat('a', 40));
        $fresh = CartOwner::guest(str_repeat('b', 40));
        $customer = CartOwner::customer(7);

        foreach ([[$old, '-5 days -1 second'], [$fresh, '-5 days'], [$customer, '-1 year']] as [$owner, $age]) {
            $this->carts->createIfMissing($owner);
            $this->carts->changedAt($owner, $clock->now()->modify($age));
        }

        expect((new DeleteOldGuestCarts($this->carts, $clock))->handle())->toBe(1)
            ->and($this->carts->find($old))->toBeNull()
            ->and($this->carts->find($fresh))->not->toBeNull()
            ->and($this->carts->find($customer))->not->toBeNull();
    });
});
