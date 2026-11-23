<?php

declare(strict_types=1);

use App\Modules\Content\Domain\Enums\Weekday;
use App\Modules\Content\Presentation\Support\ShopFormat;

/**
 * 11:00 to 23:00 every day, with changes.
 *
 * @param  array<string, array{string, string}|null>  $changes
 * @return array<string, array{string, string}|null>
 */
function weekWith(array $changes = []): array
{
    $week = [];

    foreach (Weekday::cases() as $day) {
        $week[$day->value] = ['11:00', '23:00'];
    }

    return [...$week, ...$changes];
}

it('writes opening hours in few lines', function (array $week, array $lines): void {
    $written = array_map(fn (array $line): string => "{$line['days']}: {$line['hours']}", ShopFormat::openingHours($week));

    expect($written)->toBe($lines);
})->with([
    'all the same' => [weekWith(), ['Every day: 11:00 AM to 11:00 PM']],
    'closed on Friday' => [weekWith(['friday' => null]), ['Saturday to Thursday: 11:00 AM to 11:00 PM', 'Friday: Closed']],
    'two days' => [
        weekWith(['thursday' => ['15:30', '02:00'], 'friday' => ['15:30', '02:00']]),
        ['Saturday to Wednesday: 11:00 AM to 11:00 PM', 'Thursday and Friday: 3:30 PM to 2:00 AM'],
    ],
    'a different day in the middle' => [
        weekWith(['sunday' => null, 'monday' => ['00:00', '00:00']]),
        ['Saturday: 11:00 AM to 11:00 PM', 'Sunday: Closed', 'Monday: Open 24 hours', 'Tuesday to Friday: 11:00 AM to 11:00 PM'],
    ],
    'never open' => [[], ['Every day: Closed']],
]);

it('makes a phone link', function (string $phone, string $href): void {
    expect(ShopFormat::phoneHref($phone))->toBe($href);
})->with([
    ['01712 345-678', 'tel:01712345678'],
    ['+880 (1712) 345678', 'tel:+8801712345678'],
    ['16247', 'tel:16247'],
]);
