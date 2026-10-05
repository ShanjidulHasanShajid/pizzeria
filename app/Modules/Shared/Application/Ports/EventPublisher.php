<?php

declare(strict_types=1);

namespace App\Modules\Shared\Application\Ports;

use App\Modules\Shared\Domain\Events\DomainEvent;

interface EventPublisher
{
    /**
     * Sends the events to whoever listens. Adapters must deliver them only
     * after the current database transaction has been committed.
     */
    public function publish(DomainEvent ...$events): void;
}
