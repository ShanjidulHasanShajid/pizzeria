<?php

declare(strict_types=1);

use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\DateRange;

function october(string $time): DateTimeImmutable
{
    return new DateTimeImmutable('2026-10-'.$time);
}

it('is active between its start and its end, both included', function (string $moment, bool $expected) {
    $range = DateRange::between(october('01 00:00:00'), october('31 23:59:59'));

    expect($range->isActiveAt(new DateTimeImmutable($moment)))->toBe($expected);
})->with([
    'before the start' => ['2026-09-30 23:59:59', false],
    'at the start' => ['2026-10-01 00:00:00', true],
    'in the middle' => ['2026-10-15 12:00:00', true],
    'at the end' => ['2026-10-31 23:59:59', true],
    'after the end' => ['2026-11-01 00:00:00', false],
]);

it('has no start limit when the start is null', function () {
    $range = DateRange::between(null, october('10 00:00:00'));

    expect($range->isActiveAt(new DateTimeImmutable('2000-01-01')))->toBeTrue()
        ->and($range->isActiveAt(october('11 00:00:00')))->toBeFalse();
});

it('has no end limit when the end is null', function () {
    $range = DateRange::between(october('10 00:00:00'), null);

    expect($range->isActiveAt(new DateTimeImmutable('2099-01-01')))->toBeTrue()
        ->and($range->isActiveAt(october('09 00:00:00')))->toBeFalse();
});

it('is always active when it has no limits', function () {
    expect(DateRange::always()->isActiveAt(new DateTimeImmutable('1990-01-01')))->toBeTrue();
});

it('rejects an end before the start', function () {
    expect(fn () => DateRange::between(october('10 00:00:00'), october('09 00:00:00')))
        ->toThrow(InvalidValue::class);
});
