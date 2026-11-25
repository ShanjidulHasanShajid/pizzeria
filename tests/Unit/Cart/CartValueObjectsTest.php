<?php

declare(strict_types=1);

use App\Modules\Cart\Domain\ValueObjects\CartOwner;
use App\Modules\Cart\Domain\ValueObjects\ChosenOptions;
use App\Modules\Cart\Domain\ValueObjects\KitchenNote;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;

describe('cart owner', function (): void {
    it('is either a customer or a guest', function (): void {
        $customer = CartOwner::customer(7);
        $guest = CartOwner::guest(str_repeat('a', 40));

        expect($customer->isGuest())->toBeFalse()
            ->and($customer->userId())->toBe(7)
            ->and($customer->guestToken())->toBeNull()
            ->and($guest->isGuest())->toBeTrue()
            ->and($guest->userId())->toBeNull()
            ->and($guest->guestToken())->toBe(str_repeat('a', 40));
    });

    it('compares by value', function (): void {
        expect(CartOwner::customer(7)->equals(CartOwner::customer(7)))->toBeTrue()
            ->and(CartOwner::customer(7)->equals(CartOwner::customer(8)))->toBeFalse()
            ->and(CartOwner::customer(7)->equals(CartOwner::guest(str_repeat('a', 40))))->toBeFalse();
    });

    it('refuses a token that we did not make', function (string $token): void {
        CartOwner::guest($token);
    })->with([
        'empty' => [''],
        'too short' => ['abc'],
        'too long' => [str_repeat('a', 41)],
        'strange characters' => [str_repeat('-', 40)],
        'line break at the end' => [str_repeat('a', 40)."\n"],
    ])->throws(InvalidValue::class);

    it('refuses a user id that cannot exist', function (): void {
        CartOwner::customer(0);
    })->throws(InvalidValue::class);
});

describe('chosen options', function (): void {
    it('does not care about the order of the clicks', function (): void {
        $one = ChosenOptions::of([2 => [9, 4], 1 => [3]]);
        $other = ChosenOptions::of([1 => [3], 2 => [4, 9]]);

        expect($one->equals($other))->toBeTrue()
            ->and($one->toArray())->toBe([1 => [3], 2 => [4, 9]]);
    });

    it('treats a group without picks like no group', function (): void {
        expect(ChosenOptions::of([1 => []])->equals(ChosenOptions::none()))->toBeTrue();
    });

    it('tells different picks apart', function (): void {
        expect(ChosenOptions::of([1 => [3]])->equals(ChosenOptions::of([1 => [4]])))->toBeFalse();
    });

    it('refuses ids that cannot exist and the same option twice', function (array $choices): void {
        ChosenOptions::of($choices);
    })->with([
        'group 0' => [[0 => [1]]],
        'option 0' => [[1 => [0]]],
        'negative option' => [[1 => [-5]]],
        'twice' => [[1 => [3, 3]]],
    ])->throws(InvalidValue::class);
});

describe('kitchen note', function (): void {
    it('cuts outer spaces and knows when it is empty', function (): void {
        expect(KitchenNote::of('  less spicy ')->text())->toBe('less spicy')
            ->and(KitchenNote::of("  \n ")->isEmpty())->toBeTrue()
            ->and(KitchenNote::of(null)->text())->toBeNull()
            ->and(KitchenNote::of(null)->equals(KitchenNote::none()))->toBeTrue();
    });

    it('treats line breaks of every system alike', function (): void {
        expect(KitchenNote::of("a\r\nb")->equals(KitchenNote::of("a\nb")))->toBeTrue();
    });

    it('counts letters, not bytes', function (): void {
        expect(KitchenNote::of(str_repeat('ঝ', 200))->text())->toBe(str_repeat('ঝ', 200));
        expect(fn () => KitchenNote::of(str_repeat('ঝ', 201)))->toThrow(InvalidValue::class, '200');
    });
});
