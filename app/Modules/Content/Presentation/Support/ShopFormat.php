<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Support;

use App\Modules\Content\Domain\Enums\Weekday;
use DateTimeImmutable;

/**
 * How the shop pages write phone links and opening hours.
 */
final class ShopFormat
{
    /** "+880 1712-345678" gives "tel:+8801712345678". */
    public static function phoneHref(string $phone): string
    {
        return 'tel:'.preg_replace('/(?!^\+)\D/', '', $phone);
    }

    /**
     * Days in a row with the same hours become one line: "Saturday to Thursday".
     *
     * @param  array<string, array{string, string}|null>  $week  weekday => times; null is closed
     * @return list<array{days: string, hours: string}>
     */
    public static function openingHours(array $week): array
    {
        $groups = [];

        foreach (Weekday::cases() as $day) {
            $hours = self::hours($week[$day->value] ?? null);
            $last = array_key_last($groups);

            if ($last !== null && $groups[$last]['hours'] === $hours) {
                $groups[$last]['days'][] = $day->label();
            } else {
                $groups[] = ['days' => [$day->label()], 'hours' => $hours];
            }
        }

        return array_map(fn (array $group): array => ['days' => self::days($group['days']), 'hours' => $group['hours']], $groups);
    }

    /**
     * @param  array{string, string}|null  $times
     */
    private static function hours(?array $times): string
    {
        return match (true) {
            $times === null => 'Closed',
            $times === ['00:00', '00:00'] => 'Open 24 hours',
            default => self::time($times[0]).' to '.self::time($times[1]),
        };
    }

    private static function time(string $time): string
    {
        return (new DateTimeImmutable("2000-01-01 {$time}"))->format('g:i A');
    }

    /**
     * @param  non-empty-list<string>  $days
     */
    private static function days(array $days): string
    {
        return match (count($days)) {
            1 => $days[0],
            2 => "{$days[0]} and {$days[1]}",
            7 => 'Every day',
            default => $days[0].' to '.$days[count($days) - 1],
        };
    }
}
