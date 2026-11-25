<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Events\UserLoggedIn;
use App\Modules\Identity\Application\UseCases\AnnounceLogin;
use App\Modules\Shared\Application\Ports\EventPublisher;
use App\Modules\Shared\Domain\Events\DomainEvent;
use App\Modules\Shared\Infrastructure\Adapters\FrozenClock;

it('tells everybody who signed in and when', function (): void {
    $publisher = new class implements EventPublisher
    {
        /** @var list<DomainEvent> */
        public array $events = [];

        public function publish(DomainEvent ...$events): void
        {
            array_push($this->events, ...$events);
        }
    };
    $clock = FrozenClock::at('2026-11-25 10:00:00');

    (new AnnounceLogin($publisher, $clock))->handle(7);

    expect($publisher->events)->toHaveCount(1)
        ->and($publisher->events[0])->toBeInstanceOf(UserLoggedIn::class)
        ->and($publisher->events[0]->userId)->toBe(7)
        ->and($publisher->events[0]->occurredAt)->toEqual($clock->now());
});
