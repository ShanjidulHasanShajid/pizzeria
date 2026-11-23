<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Providers;

use App\Modules\Content\Application\Queries\SettingsQuery;
use App\Modules\Content\Domain\Repositories\SettingsRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedSettingsRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\EloquentSettingsRepository;
use App\Modules\Content\Infrastructure\Queries\RepositorySettingsQuery;
use App\Modules\Shared\Infrastructure\Providers\ModuleServiceProvider;

final class ContentServiceProvider extends ModuleServiceProvider
{
    /**
     * Interface => implementation pairs for this module.
     * Laravel registers this array automatically.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        SettingsRepository::class => CachedSettingsRepository::class,
        SettingsQuery::class => RepositorySettingsQuery::class,
    ];

    public function register(): void
    {
        parent::register();

        // Everybody gets the cached repository; only the cached one gets the real one inside.
        $this->app->when(CachedSettingsRepository::class)
            ->needs(SettingsRepository::class)
            ->give(EloquentSettingsRepository::class);
    }

    protected function moduleName(): string
    {
        return 'Content';
    }
}
