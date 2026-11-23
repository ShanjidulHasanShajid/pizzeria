<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\ValueObjects;

use App\Modules\Content\Domain\Enums\Weekday;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use DateInterval;
use DateTimeImmutable;

/**
 * When a branch is open in a normal week: one period per day, or closed.
 * A period that ends at or before its start ends on the next day: 18:00 to 02:00.
 * 00:00 to 00:00 means around the clock. The opening minute is inside, the closing minute is not.
 * All moments are read in their own time zone, so callers pass shop time (the Clock port).
 */
final readonly class OpeningHours
{
    private const MINUTES_PER_DAY = 1440;

    /**
     * @param  array<string, array{int, int}>  $periods  weekday => minutes after midnight, the end may be after 1440; closed days are missing
     */
    private function __construct(private array $periods) {}

    public static function alwaysClosed(): self
    {
        return new self([]);
    }

    /**
     * @param  array<string, array{string, string}|null>  $week  weekday => ["11:00", "23:00"]; null or missing is closed
     */
    public static function fromWeek(array $week): self
    {
        $periods = [];
        $errors = [];

        foreach (Weekday::cases() as $day) {
            $times = $week[$day->value] ?? null;

            if ($times === null) {
                continue;
            }

            $opens = self::minutes($times[0]);
            $closes = self::minutes($times[1]);

            if ($opens === null || $closes === null) {
                $errors["hours.{$day->value}"] = ['Enter both times, for example 11:00 and 23:00.'];
            } elseif ($opens === $closes && $opens !== 0) {
                $errors["hours.{$day->value}"] = ['Opening and closing time are the same. For open around the clock use 00:00 and 00:00.'];
            } else {
                $periods[$day->value] = [$opens, $closes > $opens ? $closes : $closes + self::MINUTES_PER_DAY];
            }
        }

        foreach ($periods as $name => [, $closes]) {
            $next = $periods[Weekday::from($name)->next()->value] ?? null;

            if ($next !== null && $closes - self::MINUTES_PER_DAY > $next[0]) {
                $errors["hours.{$name}"] = ['This closes after the next day has already opened.'];
            }
        }

        if ($errors !== []) {
            throw ValidationFailed::withErrors($errors);
        }

        return new self($periods);
    }

    /**
     * @return array<string, array{string, string}|null> all seven days
     */
    public function toWeek(): array
    {
        $week = [];

        foreach (Weekday::cases() as $day) {
            $period = $this->periods[$day->value] ?? null;
            $week[$day->value] = $period === null ? null : [self::time($period[0]), self::time($period[1])];
        }

        return $week;
    }

    public function isOpenAt(DateTimeImmutable $moment): bool
    {
        $today = Weekday::of($moment);
        $minute = (int) $moment->format('G') * 60 + (int) $moment->format('i');

        // Either in today's period, or still in the one that began yesterday.
        return $this->covers($today, $minute)
            || $this->covers($today->previous(), $minute + self::MINUTES_PER_DAY);
    }

    /**
     * The first opening after this moment, or null when the branch never opens.
     * Ask isOpenAt() first: while it is open, this is the opening after the current one.
     */
    public function nextOpeningAfter(DateTimeImmutable $moment): ?DateTimeImmutable
    {
        for ($days = 0; $days <= 7; $days++) {
            $date = $moment->add(new DateInterval("P{$days}D"));
            $period = $this->periods[Weekday::of($date)->value] ?? null;

            if ($period === null) {
                continue;
            }

            $opening = $date->setTime(intdiv($period[0], 60), $period[0] % 60);

            if ($opening > $moment) {
                return $opening;
            }
        }

        return null;
    }

    private function covers(Weekday $day, int $minute): bool
    {
        $period = $this->periods[$day->value] ?? null;

        return $period !== null && $minute >= $period[0] && $minute < $period[1];
    }

    private static function minutes(string $time): ?int
    {
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/D', $time, $parts) !== 1) {
            return null;
        }

        return (int) $parts[1] * 60 + (int) $parts[2];
    }

    private static function time(int $minutes): string
    {
        $minutes %= self::MINUTES_PER_DAY;

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
