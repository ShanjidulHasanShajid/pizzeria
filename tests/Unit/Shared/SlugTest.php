<?php

declare(strict_types=1);

use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Slug;

it('accepts a clean slug', function (string $value) {
    expect(Slug::fromString($value)->value())->toBe($value);
})->with(['pizza', 'margherita-pizza', 'combo-2-for-1', '7up']);

it('rejects a slug that is not clean', function (string $value) {
    expect(fn () => Slug::fromString($value))->toThrow(InvalidValue::class);
})->with(['', 'Pizza', 'two words', '-pizza', 'pizza-', 'big--pizza', 'pizza_1', 'piżża']);

it('rejects a slug longer than 120 characters', function () {
    expect(fn () => Slug::fromString(str_repeat('a', 121)))->toThrow(InvalidValue::class);
});

it('compares by value', function () {
    expect(Slug::fromString('a')->equals(Slug::fromString('a')))->toBeTrue();
});
