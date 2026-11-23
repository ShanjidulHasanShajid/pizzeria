<?php

declare(strict_types=1);

use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Enums\PriceMode;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

it('charges a flat price at every size, also for a variant without a size', function (): void {
    $olives = Option::withFlatPrice('Olives', Money::fromTaka('60'), SortOrder::first(), isVeg: true);

    expect($olives->priceMode())->toBe(PriceMode::Flat)
        ->and($olives->priceFor(1)?->poisha())->toBe(6_000)
        ->and($olives->priceFor(3)?->poisha())->toBe(6_000)
        ->and($olives->priceFor(null)?->poisha())->toBe(6_000)
        ->and($olives->isVeg())->toBeTrue()
        ->and($olives->isAvailable())->toBeTrue();
});

it('charges per size only where a price is set, and zero means free', function (): void {
    $stuffed = Option::withPricesPerSize('Stuffed', [3 => Money::zero(), 2 => Money::fromTaka('150')], SortOrder::first());

    expect($stuffed->priceFor(2)?->poisha())->toBe(15_000)
        ->and($stuffed->priceFor(3)?->isZero())->toBeTrue()
        ->and($stuffed->priceFor(1))->toBeNull()
        ->and($stuffed->priceFor(null))->toBeNull()
        ->and(array_keys($stuffed->sizePrices()))->toBe([2, 3]);
});

it('needs a flat price in flat mode', function (): void {
    Option::reconstitute(5, 'Thin', null, false, true, SortOrder::first(), PriceMode::Flat, null, []);
})->throws(InvalidValue::class, 'Give "Thin" a price');

it('switches between flat and per-size prices without losing the other prices', function (): void {
    $stuffed = Option::withPricesPerSize('Stuffed', [2 => Money::fromTaka('150')], SortOrder::first());

    $stuffed->useFlatPrice(Money::fromTaka('120'));
    expect($stuffed->priceFor(1)?->poisha())->toBe(12_000);

    $stuffed->usePricesPerSize($stuffed->sizePrices());
    expect($stuffed->priceFor(1))->toBeNull()
        ->and($stuffed->priceFor(2)?->poisha())->toBe(15_000)
        ->and($stuffed->flatPrice()?->poisha())->toBe(12_000);
});

it('refuses an empty or too long name and a price for an invalid size', function (): void {
    expect(fn () => Option::withFlatPrice('  ', Money::zero(), SortOrder::first()))->toThrow(InvalidValue::class);
    expect(fn () => Option::withFlatPrice(str_repeat('x', 101), Money::zero(), SortOrder::first()))->toThrow(InvalidValue::class);
    expect(fn () => Option::withPricesPerSize('Stuffed', [0 => Money::zero()], SortOrder::first()))->toThrow(InvalidValue::class);
});
