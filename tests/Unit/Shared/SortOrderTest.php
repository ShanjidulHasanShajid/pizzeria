<?php

declare(strict_types=1);

use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

it('starts at 10 and moves in steps of 10', function () {
    expect(SortOrder::first()->value())->toBe(10)
        ->and(SortOrder::first()->next()->value())->toBe(20);
});

it('rejects a negative position', function () {
    expect(fn () => SortOrder::fromInt(-10))->toThrow(InvalidValue::class);
});

it('compares positions', function () {
    expect(SortOrder::fromInt(10)->isBefore(SortOrder::fromInt(20)))->toBeTrue()
        ->and(SortOrder::fromInt(20)->isBefore(SortOrder::fromInt(10)))->toBeFalse()
        ->and(SortOrder::fromInt(10)->equals(SortOrder::fromInt(10)))->toBeTrue();
});
