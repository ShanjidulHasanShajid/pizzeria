<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Providers;

use App\Modules\Content\Application\Ports\HomeCatalog;
use App\Modules\Content\Application\Queries\ContentQuery;
use App\Modules\Content\Application\Queries\SettingsQuery;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;
use App\Modules\Content\Domain\Repositories\HomeLayoutRepository;
use App\Modules\Content\Domain\Repositories\SettingsRepository;
use App\Modules\Content\Infrastructure\Adapters\CatalogHomeCatalog;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedContentItemRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedHomeLayoutRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedSettingsRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\EloquentContentItemRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\EloquentHomeLayoutRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\EloquentSettingsRepository;
use App\Modules\Content\Infrastructure\Queries\RepositoryContentQuery;
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
    }

    protected function moduleName(): string
    {
        return 'Content';
    }
}
