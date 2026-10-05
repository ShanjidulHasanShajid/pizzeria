<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Adapters;

use App\Modules\Shared\Application\Ports\EventPublisher;
use App\Modules\Shared\Domain\Events\DomainEvent;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;

final class LaravelEventPublisher implements EventPublisher
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly DatabaseManager $database,
    ) {}

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            // Runs now if no transaction is open, otherwise right after the outermost commit.
            // If the transaction rolls back, the callback is dropped and nothing is sent.
            $this->database->afterCommit(fn () => $this->events->dispatch($event));
        }
    }
}
