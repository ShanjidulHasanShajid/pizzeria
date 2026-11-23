<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\Enums;

use DateTimeImmutable;

/**
 * In the order of the week in Bangladesh: Saturday first.
 */
enum Weekday: string
{
    case Saturday = 'saturday';
    case Sunday = 'sunday';
    case Monday = 'monday';
    case Tuesday = 'tuesday';
    case Wednesday = 'wednesday';
    case Thursday = 'thursday';
    case Friday = 'friday';

    public static function of(DateTimeImmutable $moment): self
    {
        return self::from(strtolower($moment->format('l')));
    }

    public function next(): self
    {
        return $this->moved(1);
    }

    public function previous(): self
    {
        return $this->moved(6);
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    private function moved(int $days): self
    {
        $week = self::cases();

        return $week[((int) array_search($this, $week, true) + $days) % 7];
    }
}
