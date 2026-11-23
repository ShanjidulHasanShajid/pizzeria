<?php

declare(strict_types=1);

use App\Modules\Content\Domain\Enums\Weekday;
use App\Modules\Content\Domain\ValueObjects\OpeningHours;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;

/*
| 2026-11-23 is a Monday, 2026-11-27 a Friday.
| The branch of most tests: 11:00 to 23:00, Thursday 18:00 to 02:00 (until Friday), Friday closed.
*/
function branchHours(): OpeningHours
{
    $week = [];

    foreach (Weekday::cases() as $day) {
        $week[$day->value] = ['11:00', '23:00'];
    }

    return OpeningHours::fromWeek([...$week, 'thursday' => ['18:00', '02:00'], 'friday' => null]);
}

it('knows whether it is open', function (string $moment, bool $isOpen): void {
    expect(branchHours()->isOpenAt(new DateTimeImmutable($moment)))->toBe($isOpen);
})->with([
    'before opening' => ['2026-11-23 10:59:59', false],
    'at the opening minute' => ['2026-11-23 11:00', true],
    'in the last minute' => ['2026-11-23 22:59:59', true],
    'at the closing minute' => ['2026-11-23 23:00', false],
    'Thursday before the evening' => ['2026-11-26 17:59', false],
    'Thursday before midnight' => ['2026-11-26 23:59', true],
    'after midnight, on the closed Friday' => ['2026-11-27 01:59', true],
    'at the end of the Thursday night' => ['2026-11-27 02:00', false],
    'on the closed day' => ['2026-11-27 15:00', false],
    'one night earlier at the same time' => ['2026-11-26 01:00', false],
]);

it('finds the next opening', function (string $moment, string $next): void {
    expect(branchHours()->nextOpeningAfter(new DateTimeImmutable($moment))?->format('Y-m-d H:i:s'))->toBe($next);
})->with([
    'later today' => ['2026-11-23 09:30', '2026-11-23 11:00:00'],
    'tomorrow, while open' => ['2026-11-23 11:00', '2026-11-24 11:00:00'],
    'in the evening' => ['2026-11-25 23:30', '2026-11-26 18:00:00'],
    'over the closed day' => ['2026-11-26 19:00', '2026-11-28 11:00:00'],
    'from the closed day' => ['2026-11-27 12:00', '2026-11-28 11:00:00'],
]);

it('finds an opening a whole week later, and none when it is always closed', function (): void {
    $mondays = OpeningHours::fromWeek(['monday' => ['11:00', '12:00']]);

    expect($mondays->nextOpeningAfter(new DateTimeImmutable('2026-11-23 11:30'))?->format('Y-m-d H:i'))->toBe('2026-11-30 11:00')
        ->and(OpeningHours::alwaysClosed()->nextOpeningAfter(new DateTimeImmutable('2026-11-23 11:30')))->toBeNull()
        ->and(OpeningHours::alwaysClosed()->isOpenAt(new DateTimeImmutable('2026-11-23 11:30')))->toBeFalse();
});

it('can be open around the clock, also over the end of the week', function (): void {
    $always = OpeningHours::fromWeek(array_fill_keys(array_map(fn (Weekday $day): string => $day->value, Weekday::cases()), ['00:00', '00:00']));
    $fridayNight = OpeningHours::fromWeek(['friday' => ['20:00', '03:00']]);

    expect($always->isOpenAt(new DateTimeImmutable('2026-11-27 23:59:59')))->toBeTrue()
        ->and($always->isOpenAt(new DateTimeImmutable('2026-11-28 00:00')))->toBeTrue()
        ->and($fridayNight->isOpenAt(new DateTimeImmutable('2026-11-28 02:59')))->toBeTrue();
});

it('gives the week back as it was entered', function (): void {
    $week = branchHours()->toWeek();

    expect($week)->toHaveCount(7)
        ->and($week['saturday'])->toBe(['11:00', '23:00'])
        ->and($week['thursday'])->toBe(['18:00', '02:00'])
        ->and($week['friday'])->toBeNull()
        ->and(OpeningHours::fromWeek($week)->toWeek())->toBe($week);
});

it('refuses times that do not work', function (array $week, string $day): void {
    try {
        OpeningHours::fromWeek($week);
    } catch (ValidationFailed $failure) {
        expect(array_keys($failure->errors()))->toBe(["hours.{$day}"]);

        return;
    }

    throw new RuntimeException('These hours must be refused.');
})->with([
    'a missing time' => [['monday' => ['11:00', '']], 'monday'],
    'no time of the day' => [['monday' => ['24:00', '25:00']], 'monday'],
    'another notation' => [['monday' => ['11 AM', '23:00']], 'monday'],
    'the same time' => [['monday' => ['11:00', '11:00']], 'monday'],
    'closing after the next day opened' => [['monday' => ['18:00', '12:00'], 'tuesday' => ['11:00', '23:00']], 'monday'],
    'Friday night into Saturday' => [['friday' => ['18:00', '12:00'], 'saturday' => ['11:00', '23:00']], 'friday'],
]);
