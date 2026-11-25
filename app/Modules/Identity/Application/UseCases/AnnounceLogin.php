<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Identity\Application\Events\UserLoggedIn;
use App\Modules\Shared\Application\Ports\Clock;
use App\Modules\Shared\Application\Ports\EventPublisher;

/**
 * Tells the other modules that a user signed in. Called only after CheckLoginAllowed: Laravel's own
 * Login event comes too early, it fires for blocked users too.
 */
final class AnnounceLogin
{
    public function __construct(
        private readonly EventPublisher $events,
        private readonly Clock $clock,
    ) {}

    public function handle(int $userId): void
    {
        $this->events->publish(new UserLoggedIn($userId, $this->clock->now()));
    }
}
