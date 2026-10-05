<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Adapters;

use App\Modules\Shared\Application\Ports\Clock;
use DateTimeImmutable;
use DateTimeZone;

/**
 * A clock that stands still, for tests. Move it yourself with advance().
 */
final class FrozenClock implements Clock
{
    public function __construct(private DateTimeImmutable $now) {}

    public static function at(string $dateTime, string $timezone = 'Asia/Dhaka'): self
    {
        return new self(new DateTimeImmutable($dateTime, new DateTimeZone($timezone)));
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    /**
     * Example: advance('+1 day') or advance('+30 minutes').
     */
    public function advance(string $modifier): void
    {
        $this->now = $this->now->modify($modifier);
    }
}
