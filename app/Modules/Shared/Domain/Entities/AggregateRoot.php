<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Entities;

use App\Modules\Shared\Domain\Events\DomainEvent;

/**
 * Base for the "main" entity of a group of objects (Order, Cart, Product).
 * It remembers events while it changes, and hands them over once, after saving.
 */
abstract class AggregateRoot
{
    /** @var list<DomainEvent> */
    private array $recordedEvents = [];

    protected function record(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }

    /**
     * Returns the recorded events and forgets them, so they are published only once.
     *
     * @return list<DomainEvent>
     */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }
}
