<?php

declare(strict_types=1);

use App\Modules\Shared\Domain\Entities\AggregateRoot;
use App\Modules\Shared\Domain\Events\DomainEvent;

final readonly class FixtureThingHappened extends DomainEvent {}

final class FixtureAggregate extends AggregateRoot
{
    public function doSomething(DateTimeImmutable $at): void
    {
        $this->record(new FixtureThingHappened($at));
    }
}

it('names an event after its class and keeps the time it happened', function () {
    $at = new DateTimeImmutable('2026-10-05 12:00:00');
    $event = new FixtureThingHappened($at);

    expect($event->name())->toBe(FixtureThingHappened::class)
        ->and($event->occurredAt)->toBe($at);
});

it('records events while it changes', function () {
    $aggregate = new FixtureAggregate;
    $aggregate->doSomething(new DateTimeImmutable);
    $aggregate->doSomething(new DateTimeImmutable);

    expect($aggregate->releaseEvents())->toHaveCount(2);
});

it('hands over its events only once', function () {
    $aggregate = new FixtureAggregate;
    $aggregate->doSomething(new DateTimeImmutable);
    $aggregate->releaseEvents();

    expect($aggregate->releaseEvents())->toBe([]);
});
