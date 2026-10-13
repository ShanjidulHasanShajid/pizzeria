<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\ValueObjects;

use App\Modules\Catalog\Domain\Enums\LeadTimeClass;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;

/**
 * How long a product takes: a class plus the minutes from ordering until it can be ready.
 * Examples: made to order in 25 minutes, same day with 180 minutes notice, next day (1440).
 */
final readonly class LeadTime
{
    /** One week. Anything longer is almost certainly a typing mistake. */
    public const MAX_MINUTES = 10_080;

    private function __construct(
        private LeadTimeClass $class,
        private int $minutes,
    ) {
        if ($minutes < 0 || $minutes > self::MAX_MINUTES) {
            throw InvalidValue::because('A lead time must be between 0 minutes and 7 days.');
        }
    }

    public static function of(LeadTimeClass $class, int $minutes): self
    {
        return new self($class, $minutes);
    }

    public static function madeToOrder(int $minutes): self
    {
        return new self(LeadTimeClass::MadeToOrder, $minutes);
    }

    public static function sameDay(int $minutes): self
    {
        return new self(LeadTimeClass::SameDay, $minutes);
    }

    public static function nextDay(int $minutes): self
    {
        return new self(LeadTimeClass::NextDay, $minutes);
    }

    public function leadTimeClass(): LeadTimeClass
    {
        return $this->class;
    }

    public function minutes(): int
    {
        return $this->minutes;
    }

    /**
     * Short text for the badge on a product card.
     */
    public function label(): string
    {
        return match ($this->class) {
            LeadTimeClass::MadeToOrder => $this->minutes === 0
                ? 'Ready now'
                : 'Ready in about '.self::duration($this->minutes),
            LeadTimeClass::SameDay => 'Same day',
            LeadTimeClass::NextDay => 'Next day',
        };
    }

    /**
     * Used later to find the slowest item in a cart.
     */
    public function isLongerThan(self $other): bool
    {
        return $this->minutes > $other->minutes;
    }

    public function equals(self $other): bool
    {
        return $this->class === $other->class && $this->minutes === $other->minutes;
    }

    /** 25 -> "25 min", 120 -> "2 h", 90 -> "1 h 30 min". */
    private static function duration(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $rest === 0 ? "{$hours} h" : "{$hours} h {$rest} min";
    }
}
