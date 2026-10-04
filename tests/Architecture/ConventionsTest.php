<?php

declare(strict_types=1);

// Abstract base classes cannot be final. When one appears (Phase 3), add it
// with ->ignoring(SomeBaseClass::class) and a comment saying why.
arch('domain and application classes are final')
    ->expect(['App\Modules\*\Domain', 'App\Modules\*\Application'])
    ->classes()
    ->toBeFinal();

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
