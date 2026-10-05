<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use DateTimeImmutable;

/**
 * A time window with an optional start and an optional end.
 * No start means "from the beginning", no end means "forever".
 * Both the start moment and the end moment are inside the range.
 * Used for coupons, banners and scheduled content.
 */
final readonly class DateRange
{
    private function __construct(
        private ?DateTimeImmutable $start,
        private ?DateTimeImmutable $end,
    ) {
        if ($start !== null && $end !== null && $end < $start) {
            throw InvalidValue::because('The end of a date range cannot be before its start.');
        }
    }

    public static function between(?DateTimeImmutable $start, ?DateTimeImmutable $end): self
    {
        return new self($start, $end);
    }

    public static function always(): self
    {
        return new self(null, null);
    }

    public function start(): ?DateTimeImmutable
    {
        return $this->start;
    }

    public function end(): ?DateTimeImmutable
    {
        return $this->end;
    }

    /**
     * The caller passes the moment (from the Clock port). Domain code never asks for "now".
     */
    public function isActiveAt(DateTimeImmutable $moment): bool
    {
        if ($this->start !== null && $moment < $this->start) {
            return false;
        }

        return $this->end === null || $moment <= $this->end;
    }
}
