<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Adapters;

use App\Modules\Shared\Application\Ports\Clock;
use DateTimeImmutable;
use DateTimeZone;

/**
 * The real clock, in the application's time zone (Asia/Dhaka).
 */
final class SystemClock implements Clock
{
    private readonly DateTimeZone $timezone;

    public function __construct(string $timezone = 'UTC')
    {
        $this->timezone = new DateTimeZone($timezone);
    }

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->timezone);
    }
}
