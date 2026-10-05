<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Providers;

use App\Modules\Shared\Application\Ports\Clock;
use App\Modules\Shared\Application\Ports\EventPublisher;
use App\Modules\Shared\Application\Ports\FileStorage;
use App\Modules\Shared\Application\Ports\ImageProcessor;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Infrastructure\Adapters\InterventionImageProcessor;
use App\Modules\Shared\Infrastructure\Adapters\LaravelEventPublisher;
use App\Modules\Shared\Infrastructure\Adapters\LaravelFileStorage;
use App\Modules\Shared\Infrastructure\Adapters\LaravelTransactionManager;
use App\Modules\Shared\Infrastructure\Adapters\SystemClock;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

final class SharedServiceProvider extends ModuleServiceProvider
{
    /**
     * Interface => implementation pairs. Laravel registers this array automatically.
     * Use this for adapters whose constructor Laravel can fill in by itself.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        TransactionManager::class => LaravelTransactionManager::class,
        EventPublisher::class => LaravelEventPublisher::class,
        ImageProcessor::class => InterventionImageProcessor::class,
    ];

    public function register(): void
    {
        parent::register();

        // These adapters need a plain value (a time zone, a disk name),
        // so we tell the container exactly how to build them.
        $this->app->singleton(
            Clock::class,
            fn (): Clock => new SystemClock((string) config('app.timezone')),
        );

        $this->app->bind(
            FileStorage::class,
            fn ($app): FileStorage => new LaravelFileStorage($app->make(FilesystemFactory::class), 'public'),
        );
    }

    protected function moduleName(): string
    {
        return 'Shared';
    }
}
