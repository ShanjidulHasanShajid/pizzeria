<?php

declare(strict_types=1);
use App\Modules\Shared\Domain\Entities\AggregateRoot;
use App\Modules\Shared\Domain\Events\DomainEvent;
use App\Modules\Shared\Domain\Exceptions\DomainException;

// Abstract base classes cannot be final. They are listed here on purpose,
// each one is built to be extended by other classes.
arch('domain and application classes are final')
    ->expect(['App\Modules\*\Domain', 'App\Modules\*\Application'])
    ->classes()
    ->toBeFinal()
    ->ignoring([
        DomainException::class,
        DomainEvent::class,
        AggregateRoot::class,
    ]);

arch('value objects are readonly')
    ->expect('App\Modules\*\Domain\ValueObjects')
    ->classes()
    ->toBeReadonly();

arch('application DTOs are readonly')
    ->expect('App\Modules\*\Application\DTOs')
    ->classes()
    ->toBeReadonly();

arch('domain repositories are interfaces')
    ->expect('App\Modules\*\Domain\Repositories')
    ->toBeInterfaces();

arch('no debugging functions in app code')
    ->expect(['dd', 'ddd', 'dump', 'var_dump', 'ray'])
    ->not->toBeUsedIn('App');

arch('shared ports are interfaces')
    ->expect('App\Modules\Shared\Application\Ports')
    ->toBeInterfaces();
