<?php

declare(strict_types=1);

use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\Percentage;
use App\Modules\Shared\Domain\ValueObjects\Quantity;

it('creates money from poisha', function () {
    expect(Money::fromPoisha(125000)->poisha())->toBe(125000);
});

it('creates money from taka text', function (string|int $taka, int $poisha) {
    expect(Money::fromTaka($taka)->poisha())->toBe($poisha);
})->with([
    ['1250', 125000],
    ['250.5', 25050],
    ['250.50', 25050],
    ['0.05', 5],
    [300, 30000],
]);

it('rejects taka text that is not a plain amount', function (string $taka) {
    expect(fn () => Money::fromTaka($taka))->toThrow(InvalidValue::class);
})->with(['abc', '-5', '12.345', '1,250', '', '1e3']);

it('never accepts negative poisha', function () {
    expect(fn () => Money::fromPoisha(-1))->toThrow(InvalidValue::class);
});

it('adds two amounts', function () {
    $total = Money::fromPoisha(1500)->add(Money::fromPoisha(250));

    expect($total->poisha())->toBe(1750);
});

it('does not change the original amount when adding', function () {
    $original = Money::fromPoisha(1500);
    $original->add(Money::fromPoisha(250));

    expect($original->poisha())->toBe(1500);
});

it('subtracts an amount', function () {
    expect(Money::fromPoisha(1500)->subtract(Money::fromPoisha(500))->poisha())->toBe(1000);
});

it('refuses to subtract more than it has', function () {
    expect(fn () => Money::fromPoisha(500)->subtract(Money::fromPoisha(501)))
        ->toThrow(InvalidValue::class);
});

it('multiplies by a quantity', function () {
    expect(Money::fromPoisha(45000)->multiply(Quantity::of(3))->poisha())->toBe(135000);
});

it('takes a percentage and rounds half up', function (int $poisha, string $percent, int $expected) {
    $result = Money::fromPoisha($poisha)->percentage(Percentage::fromString($percent));

    expect($result->poisha())->toBe($expected);
})->with([
    'exact' => [100000, '10', 10000],
    'rounds down' => [333, '10', 33],
    'half goes up' => [335, '10', 34],
    'decimal percent' => [100000, '12.5', 12500],
    'zero percent' => [100000, '0', 0],
    'full amount' => [1234, '100', 1234],
]);

it('compares amounts', function () {
    $small = Money::fromPoisha(100);
    $big = Money::fromPoisha(200);

    expect($big->isGreaterThan($small))->toBeTrue()
        ->and($small->isLessThan($big))->toBeTrue()
        ->and($small->equals(Money::fromPoisha(100)))->toBeTrue()
        ->and($small->equals($big))->toBeFalse();
});

it('knows when it is zero', function () {
    expect(Money::zero()->isZero())->toBeTrue()
        ->and(Money::fromPoisha(1)->isZero())->toBeFalse();
});

it('prints taka for admin forms', function (int $poisha, string $text) {
    expect(Money::fromPoisha($poisha)->toTakaString())->toBe($text);
})->with([[125050, '1250.50'], [5, '0.05'], [0, '0.00']]);

it('uses the BDT currency', function () {
    expect(Money::zero()->currency())->toBe('BDT');
});
