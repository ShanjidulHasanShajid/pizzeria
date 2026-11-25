<?php

declare(strict_types=1);

use App\Modules\Cart\Domain\Entities\Cart;
use App\Modules\Cart\Domain\Entities\CartLine;
use App\Modules\Cart\Domain\Enums\CartLineType;
use App\Modules\Cart\Domain\Exceptions\CartRejected;
use App\Modules\Cart\Domain\ValueObjects\CartOwner;
use App\Modules\Cart\Domain\ValueObjects\ChosenOptions;
use App\Modules\Cart\Domain\ValueObjects\KitchenNote;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\Quantity;

function newCartLine(int $variantId = 1, int $quantity = 1, string $note = '', array $choices = [], int $taka = 100): CartLine
{
    return CartLine::standard(10, $variantId, ChosenOptions::of($choices), KitchenNote::of($note), Quantity::of($quantity), Money::fromTaka($taka));
}

function savedCartLine(int $id, int $variantId = 1, int $quantity = 1, string $note = ''): CartLine
{
    return CartLine::reconstitute($id, CartLineType::Standard, 10, $variantId, ChosenOptions::none(), KitchenNote::of($note), Quantity::of($quantity), Money::fromTaka(100));
}

function cartWith(CartLine ...$lines): Cart
{
    return Cart::reconstitute(1, CartOwner::customer(7), array_values($lines));
}

/** @return list<int> */
function quantitiesOf(Cart $cart): array
{
    return array_map(fn (CartLine $line): int => $line->quantity()->value(), $cart->lines());
}

describe('adding', function (): void {
    it('keeps different items as different lines', function (): void {
        $cart = cartWith();
        $cart->addLine(newCartLine());
        $cart->addLine(newCartLine(variantId: 2));
        $cart->addLine(newCartLine(note: 'less spicy'));
        $cart->addLine(newCartLine(choices: [1 => [5]]));

        expect($cart->lines())->toHaveCount(4);
    });

    it('merges the same item and adds the quantities', function (): void {
        $cart = cartWith(savedCartLine(1, quantity: 2));
        $cart->addLine(newCartLine(quantity: 3));

        expect(quantitiesOf($cart))->toBe([5])
            ->and($cart->lines()[0]->id())->toBe(1);
    });

    it('merges picks that were clicked in another order', function (): void {
        $cart = cartWith();
        $cart->addLine(newCartLine(choices: [1 => [5, 6]]));
        $cart->addLine(newCartLine(choices: [1 => [6, 5]]));

        expect(quantitiesOf($cart))->toBe([2]);
    });

    it('remembers the newest price when it merges', function (): void {
        $cart = cartWith(savedCartLine(1));
        $cart->addLine(newCartLine(taka: 120));

        expect($cart->lines()[0]->lastUnitPrice()->poisha())->toBe(12_000);
    });

    it('allows exactly 20 of one item and refuses the 21st without changing anything', function (): void {
        $cart = cartWith(savedCartLine(1, quantity: 18));
        $cart->addLine(newCartLine(quantity: 2));

        expect(fn () => $cart->addLine(newCartLine()))->toThrow(CartRejected::class, 'at most 20');
        expect(quantitiesOf($cart))->toBe([20]);
    });

    it('holds 30 lines and refuses the 31st, but still merges', function (): void {
        $cart = cartWith(...array_map(fn (int $id): CartLine => savedCartLine($id, variantId: $id), range(1, 30)));

        expect(fn () => $cart->addLine(newCartLine(variantId: 31)))->toThrow(CartRejected::class, 'at most 30');

        $cart->addLine(newCartLine(variantId: 30));

        expect($cart->lines())->toHaveCount(30)
            ->and($cart->lines()[29]->quantity()->value())->toBe(2);
    });
});

describe('changing', function (): void {
    it('changes the quantity of one line only', function (): void {
        $cart = cartWith(savedCartLine(1), savedCartLine(2, variantId: 2));
        $cart->changeQuantity(2, Quantity::of(7));

        expect(quantitiesOf($cart))->toBe([1, 7]);
    });

    it('changes and removes a note', function (): void {
        $cart = cartWith(savedCartLine(1, note: 'less spicy'));

        $cart->changeNote(1, KitchenNote::of('no onions'));
        expect($cart->lines()[0]->note()->text())->toBe('no onions');

        $cart->changeNote(1, KitchenNote::none());
        expect($cart->lines()[0]->note()->isEmpty())->toBeTrue();
    });

    it('merges two lines when a new note makes them the same', function (): void {
        $cart = cartWith(savedCartLine(1, quantity: 2), savedCartLine(2, quantity: 3, note: 'less spicy'));
        $cart->changeNote(2, KitchenNote::none());

        expect(quantitiesOf($cart))->toBe([5])
            ->and($cart->lines()[0]->id())->toBe(2);
    });

    it('refuses a note that would merge more than 20', function (): void {
        $cart = cartWith(savedCartLine(1, quantity: 15), savedCartLine(2, quantity: 6, note: 'less spicy'));

        expect(fn () => $cart->changeNote(2, KitchenNote::none()))->toThrow(CartRejected::class, 'at most 20');
        expect(quantitiesOf($cart))->toBe([15, 6])
            ->and($cart->lines()[1]->note()->text())->toBe('less spicy');
    });

    it('accepts the note a line already has', function (): void {
        $cart = cartWith(savedCartLine(1, quantity: 15, note: 'less spicy'));
        $cart->changeNote(1, KitchenNote::of('less spicy'));

        expect(quantitiesOf($cart))->toBe([15]);
    });

    it('removes one line', function (): void {
        $cart = cartWith(savedCartLine(1), savedCartLine(2, variantId: 2, quantity: 4));
        $cart->removeLine(1);

        expect(quantitiesOf($cart))->toBe([4]);
    });

    it('can be emptied', function (): void {
        $cart = cartWith(savedCartLine(1), savedCartLine(2, variantId: 2));
        $cart->clear();

        expect($cart->isEmpty())->toBeTrue();
    });

    it('refuses a line that is not in this cart', function (Closure $change): void {
        $change(cartWith(savedCartLine(1)));
    })->with([
        'quantity' => [fn (Cart $cart) => $cart->changeQuantity(99, Quantity::of(2))],
        'note' => [fn (Cart $cart) => $cart->changeNote(99, KitchenNote::none())],
        'remove' => [fn (Cart $cart) => $cart->removeLine(99)],
        'seen price' => [fn (Cart $cart) => $cart->rememberUnitPrice(99, Money::zero())],
    ])->throws(CartRejected::class, 'not in your cart');
});

describe('taking over another cart', function (): void {
    it('adds new lines and merges the same items', function (): void {
        $cart = cartWith(savedCartLine(1, quantity: 2));
        $leftOut = $cart->takeOver(cartWith(savedCartLine(5, quantity: 3), savedCartLine(6, variantId: 2)));

        expect($leftOut)->toBe(0)
            ->and(quantitiesOf($cart))->toBe([5, 1])
            ->and($cart->lines()[0]->id())->toBe(1)
            ->and($cart->lines()[1]->id())->toBeNull();
    });

    it('keeps the last seen price of the customer', function (): void {
        $cart = cartWith(savedCartLine(1));
        $cart->takeOver(cartWith(newCartLine(taka: 120)));

        expect($cart->lines()[0]->lastUnitPrice()->poisha())->toBe(10_000);
    });

    it('stops at 20 of one item and says how many were left out', function (): void {
        $cart = cartWith(savedCartLine(1, quantity: 18));

        expect($cart->takeOver(cartWith(savedCartLine(5, quantity: 5))))->toBe(3)
            ->and(quantitiesOf($cart))->toBe([20]);
    });

    it('leaves out everything when the line is full', function (): void {
        $cart = cartWith(savedCartLine(1, quantity: 20));

        expect($cart->takeOver(cartWith(savedCartLine(5, quantity: 5))))->toBe(5)
            ->and(quantitiesOf($cart))->toBe([20]);
    });

    it('stops at 30 lines and still merges after that', function (): void {
        $cart = cartWith(...array_map(fn (int $id): CartLine => savedCartLine($id, variantId: $id), range(1, 30)));
        $leftOut = $cart->takeOver(cartWith(savedCartLine(50, variantId: 99, quantity: 4), savedCartLine(51, variantId: 3, quantity: 2)));

        expect($leftOut)->toBe(4)
            ->and($cart->lines())->toHaveCount(30)
            ->and($cart->lines()[2]->quantity()->value())->toBe(3);
    });
});

it('notices that the price is not the one seen last', function (): void {
    $line = savedCartLine(1);

    expect($line->hasPriceChangedTo(Money::fromTaka(100)))->toBeFalse()
        ->and($line->hasPriceChangedTo(Money::fromTaka(90)))->toBeTrue();

    $line->rememberUnitPrice(Money::fromTaka(90));

    expect($line->hasPriceChangedTo(Money::fromTaka(90)))->toBeFalse();
});
