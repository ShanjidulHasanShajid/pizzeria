<?php

declare(strict_types=1);

use App\Modules\Shared\Application\Ports\EventPublisher;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Events\DomainEvent;
use Illuminate\Support\Facades\Event;

final readonly class PublisherTestEvent extends DomainEvent {}

beforeEach(function () {
    // An ArrayObject is a list we can add to from inside the listener.
    $this->received = new ArrayObject;

    Event::listen(PublisherTestEvent::class, function (PublisherTestEvent $event) {
        $this->received->append($event);
    });
});

it('delivers the event only after the transaction commits', function () {
    $event = new PublisherTestEvent(new DateTimeImmutable);

    app(TransactionManager::class)->run(function () use ($event) {
        app(EventPublisher::class)->publish($event);

        expect($this->received->getArrayCopy())->toBe([]);
    });

    expect($this->received->getArrayCopy())->toBe([$event]);
});

it('never delivers the event when the transaction rolls back', function () {
    $event = new PublisherTestEvent(new DateTimeImmutable);

    try {
        app(TransactionManager::class)->run(function () use ($event) {
            app(EventPublisher::class)->publish($event);

            throw new RuntimeException('Rolled back.');
        });
    } catch (RuntimeException) {
        // expected
    }

    expect($this->received->getArrayCopy())->toBe([]);
});

it('delivers the event at once when no transaction is open', function () {
    $event = new PublisherTestEvent(new DateTimeImmutable);

    app(EventPublisher::class)->publish($event);

    expect($this->received->getArrayCopy())->toBe([$event]);
});

it('delivers several events in order', function () {
    $first = new PublisherTestEvent(new DateTimeImmutable('2026-10-05 10:00:00'));
    $second = new PublisherTestEvent(new DateTimeImmutable('2026-10-05 11:00:00'));

    app(TransactionManager::class)->run(function () use ($first, $second) {
        app(EventPublisher::class)->publish($first, $second);
    });

    expect($this->received->getArrayCopy())->toBe([$first, $second]);
});
