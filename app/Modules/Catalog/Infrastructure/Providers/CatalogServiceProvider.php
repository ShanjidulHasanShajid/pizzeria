<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Providers;

use App\Modules\Catalog\Application\Ports\ProductOptions;
use App\Modules\Catalog\Application\Ports\SizeTierPrices;
use App\Modules\Catalog\Application\Queries\AdminCatalogQuery;
use App\Modules\Catalog\Application\Queries\MenuQuery;
use App\Modules\Catalog\Application\Queries\SitemapQuery;
use App\Modules\Catalog\Application\Queries\StorefrontCatalogQuery;
use App\Modules\Catalog\Domain\Repositories\CategoryRepository;
use App\Modules\Catalog\Domain\Repositories\KitchenStationRepository;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Catalog\Domain\Repositories\SizeTierRepository;
use App\Modules\Catalog\Domain\Repositories\TagRepository;
use App\Modules\Catalog\Infrastructure\Adapters\CustomizationProductOptions;
use App\Modules\Catalog\Infrastructure\Adapters\CustomizationSizeTierPrices;
use App\Modules\Catalog\Infrastructure\Persistence\Repositories\EloquentCategoryRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Repositories\EloquentKitchenStationRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Repositories\EloquentProductRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Repositories\EloquentSizeTierRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Repositories\EloquentTagRepository;
use App\Modules\Catalog\Infrastructure\Queries\EloquentAdminCatalogQuery;
use App\Modules\Catalog\Infrastructure\Queries\EloquentMenuQuery;
use App\Modules\Catalog\Infrastructure\Queries\EloquentSitemapQuery;
use App\Modules\Catalog\Infrastructure\Queries\EloquentStorefrontCatalogQuery;
use App\Modules\Shared\Infrastructure\Providers\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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
        MenuQuery::class => EloquentMenuQuery::class,
        SitemapQuery::class => EloquentSitemapQuery::class,
        SizeTierPrices::class => CustomizationSizeTierPrices::class,
        ProductOptions::class => CustomizationProductOptions::class,
    ];

    public function boot(): void
    {
        parent::boot();

        // Typing causes many requests, so the limit is generous: one per second on average.
        RateLimiter::for('search-suggestions', fn (Request $request): Limit => Limit::perMinute(60)->by((string) $request->ip()));
    }

    protected function moduleName(): string
    {
        return 'Catalog';
    }
}
