<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Providers;

use App\Modules\Catalog\Application\Queries\AdminCatalogQuery;
use App\Modules\Catalog\Application\Queries\StorefrontCatalogQuery;
use App\Modules\Catalog\Domain\Repositories\CategoryRepository;
use App\Modules\Catalog\Domain\Repositories\KitchenStationRepository;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Catalog\Domain\Repositories\SizeTierRepository;
use App\Modules\Catalog\Domain\Repositories\TagRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Repositories\EloquentCategoryRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Repositories\EloquentKitchenStationRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Repositories\EloquentProductRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Repositories\EloquentSizeTierRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Repositories\EloquentTagRepository;
use App\Modules\Catalog\Infrastructure\Queries\EloquentAdminCatalogQuery;
use App\Modules\Catalog\Infrastructure\Queries\EloquentStorefrontCatalogQuery;
use App\Modules\Shared\Infrastructure\Providers\ModuleServiceProvider;

final class CatalogServiceProvider extends ModuleServiceProvider
{
    /**
     * Interface => implementation pairs for this module.
     * Laravel registers this array automatically.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        KitchenStationRepository::class => EloquentKitchenStationRepository::class,
        SizeTierRepository::class => EloquentSizeTierRepository::class,
        TagRepository::class => EloquentTagRepository::class,
        CategoryRepository::class => EloquentCategoryRepository::class,
        ProductRepository::class => EloquentProductRepository::class,
        StorefrontCatalogQuery::class => EloquentStorefrontCatalogQuery::class,
        AdminCatalogQuery::class => EloquentAdminCatalogQuery::class,
    ];

    protected function moduleName(): string
    {
        return 'Catalog';
    }
}
