<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Events;

use DateTimeImmutable;

/**
 * Base for "something important happened" messages, such as OrderPlaced.
 * Events are facts in the past, so they are immutable.
 * The time is passed in (from the Clock port) because Domain code must not call now().
 */
abstract readonly class DomainEvent
{
    public function __construct(public DateTimeImmutable $occurredAt) {}

    public function name(): string
    {
        return static::class;
    }
}
