<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Events;

use App\Modules\Shared\Domain\Events\DomainEvent;
use DateTimeImmutable;

/**
 * Somebody signed in or registered, and the account may be used. It is in Application and not in
 * Domain, because other modules listen to it and may use only this module's Application layer.
 */
final readonly class UserLoggedIn extends DomainEvent
{
    public function __construct(public int $userId, DateTimeImmutable $occurredAt)
    {
        parent::__construct($occurredAt);
    }
}
