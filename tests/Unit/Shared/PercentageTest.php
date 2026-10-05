<?php

declare(strict_types=1);

use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Percentage;

it('reads percentages with up to two decimals', function (string $text, int $basisPoints) {
    expect(Percentage::fromString($text)->basisPoints())->toBe($basisPoints);
})->with([['10', 1000], ['12.5', 1250], ['33.33', 3333], ['100', 10000], ['0', 0]]);

it('rejects values outside 0 to 100 or in the wrong format', function (string $text) {
    expect(fn () => Percentage::fromString($text))->toThrow(InvalidValue::class);
})->with(['100.01', '101', '-1', '12.345', 'abc', '']);

it('prints with two decimals', function () {
    expect(Percentage::fromString('12.5')->toString())->toBe('12.50');
});

it('compares by value', function () {
    expect(Percentage::fromString('5')->equals(Percentage::fromBasisPoints(500)))->toBeTrue();
});
