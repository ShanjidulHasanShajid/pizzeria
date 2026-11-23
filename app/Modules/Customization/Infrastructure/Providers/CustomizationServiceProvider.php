<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Providers;

use App\Modules\Customization\Application\Ports\CustomizableProducts;
use App\Modules\Customization\Application\Ports\PricingProductSource;
use App\Modules\Customization\Application\Ports\SizeTiers;
use App\Modules\Customization\Application\Queries\AdminOptionQuery;
use App\Modules\Customization\Application\Queries\OptionPriceUsageQuery;
use App\Modules\Customization\Application\Queries\ProductConfigurationQuery;
use App\Modules\Customization\Application\Queries\ProductOptionsQuery;
use App\Modules\Customization\Application\Queries\TrashedOptionsQuery;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Customization\Infrastructure\Adapters\CatalogCustomizableProducts;
use App\Modules\Customization\Infrastructure\Adapters\CatalogPricingProductSource;
use App\Modules\Customization\Infrastructure\Adapters\CatalogSizeTiers;
use App\Modules\Customization\Infrastructure\Persistence\Repositories\EloquentOptionGroupRepository;
use App\Modules\Customization\Infrastructure\Persistence\Repositories\EloquentProductOptionGroupRepository;
use App\Modules\Customization\Infrastructure\Queries\CatalogProductConfigurationQuery;
use App\Modules\Customization\Infrastructure\Queries\EloquentAdminOptionQuery;
use App\Modules\Customization\Infrastructure\Queries\EloquentOptionPriceUsageQuery;
use App\Modules\Customization\Infrastructure\Queries\EloquentProductOptionsQuery;
use App\Modules\Customization\Infrastructure\Queries\EloquentTrashedOptionsQuery;
use App\Modules\Shared\Infrastructure\Providers\ModuleServiceProvider;

final class CustomizationServiceProvider extends ModuleServiceProvider
{
    /**
     * Interface => implementation pairs for this module.
     * Laravel registers this array automatically.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        OptionGroupRepository::class => EloquentOptionGroupRepository::class,
        ProductOptionGroupRepository::class => EloquentProductOptionGroupRepository::class,
        ProductOptionsQuery::class => EloquentProductOptionsQuery::class,
        ProductConfigurationQuery::class => CatalogProductConfigurationQuery::class,
        AdminOptionQuery::class => EloquentAdminOptionQuery::class,
        TrashedOptionsQuery::class => EloquentTrashedOptionsQuery::class,
        OptionPriceUsageQuery::class => EloquentOptionPriceUsageQuery::class,
        PricingProductSource::class => CatalogPricingProductSource::class,
        CustomizableProducts::class => CatalogCustomizableProducts::class,
        SizeTiers::class => CatalogSizeTiers::class,
    ];

    protected function moduleName(): string
    {
        return 'Customization';
    }
}
