<?php

declare(strict_types=1);

use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Quantity;

it('accepts 1 to 20', function (int $value) {
    expect(Quantity::of($value)->value())->toBe($value);
})->with([1, 2, 19, 20]);

it('rejects 0, negative numbers and more than 20', function (int $value) {
    expect(fn () => Quantity::of($value))->toThrow(InvalidValue::class);
})->with([0, -1, 21, 500]);

it('adds quantities', function () {
    expect(Quantity::of(5)->add(Quantity::of(7))->value())->toBe(12);
});

it('refuses an addition that goes over the limit', function () {
    expect(fn () => Quantity::of(15)->add(Quantity::of(6)))->toThrow(InvalidValue::class);
});

it('compares by value', function () {
    expect(Quantity::of(2)->equals(Quantity::of(2)))->toBeTrue();
});
