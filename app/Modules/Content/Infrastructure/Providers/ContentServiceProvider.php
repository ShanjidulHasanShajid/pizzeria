<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Providers;

use App\Modules\Content\Application\Ports\HomeCatalog;
use App\Modules\Content\Application\Queries\BranchScheduleQuery;
use App\Modules\Content\Application\Queries\ContentQuery;
use App\Modules\Content\Application\Queries\LocationQuery;
use App\Modules\Content\Application\Queries\NavigationQuery;
use App\Modules\Content\Application\Queries\SettingsQuery;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;
use App\Modules\Content\Domain\Repositories\HomeLayoutRepository;
use App\Modules\Content\Domain\Repositories\LocationRepository;
use App\Modules\Content\Domain\Repositories\NavItemRepository;
use App\Modules\Content\Domain\Repositories\SettingsRepository;
use App\Modules\Content\Infrastructure\Adapters\CatalogHomeCatalog;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedContentItemRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedHomeLayoutRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedLocationRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedNavItemRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedSettingsRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\EloquentContentItemRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\EloquentHomeLayoutRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\EloquentLocationRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\EloquentNavItemRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\EloquentSettingsRepository;
use App\Modules\Content\Infrastructure\Queries\RepositoryBranchScheduleQuery;
use App\Modules\Content\Infrastructure\Queries\RepositoryContentQuery;
use App\Modules\Content\Infrastructure\Queries\RepositoryLocationQuery;
use App\Modules\Content\Infrastructure\Queries\RepositoryNavigationQuery;
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
        ContentItemRepository::class => CachedContentItemRepository::class,
        HomeLayoutRepository::class => CachedHomeLayoutRepository::class,
        ContentQuery::class => RepositoryContentQuery::class,
        HomeCatalog::class => CatalogHomeCatalog::class,
        NavItemRepository::class => CachedNavItemRepository::class,
        NavigationQuery::class => RepositoryNavigationQuery::class,
        LocationRepository::class => CachedLocationRepository::class,
        LocationQuery::class => RepositoryLocationQuery::class,
        BranchScheduleQuery::class => RepositoryBranchScheduleQuery::class,
    ];

    public function register(): void
    {
        parent::register();

        // Everybody gets the cached repositories; only the cached ones get the real ones inside.
        $this->app->when(CachedSettingsRepository::class)
            ->needs(SettingsRepository::class)
            ->give(EloquentSettingsRepository::class);
        $this->app->when(CachedContentItemRepository::class)
            ->needs(ContentItemRepository::class)
            ->give(EloquentContentItemRepository::class);
        $this->app->when(CachedHomeLayoutRepository::class)
            ->needs(HomeLayoutRepository::class)
            ->give(EloquentHomeLayoutRepository::class);
        $this->app->when(CachedNavItemRepository::class)
            ->needs(NavItemRepository::class)
            ->give(EloquentNavItemRepository::class);
        $this->app->when(CachedLocationRepository::class)
            ->needs(LocationRepository::class)
            ->give(EloquentLocationRepository::class);
    }

    protected function moduleName(): string
    {
        return 'Content';
    }
}
