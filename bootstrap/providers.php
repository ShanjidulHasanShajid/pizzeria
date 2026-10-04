<?php

declare(strict_types=1);

use App\Modules\Cart\Infrastructure\Providers\CartServiceProvider;
use App\Modules\Catalog\Infrastructure\Providers\CatalogServiceProvider;
use App\Modules\Content\Infrastructure\Providers\ContentServiceProvider;
use App\Modules\Customers\Infrastructure\Providers\CustomersServiceProvider;
use App\Modules\Customization\Infrastructure\Providers\CustomizationServiceProvider;
use App\Modules\Identity\Infrastructure\Providers\IdentityServiceProvider;
use App\Modules\Ordering\Infrastructure\Providers\OrderingServiceProvider;
use App\Modules\Payments\Infrastructure\Providers\PaymentsServiceProvider;
use App\Modules\Promotions\Infrastructure\Providers\PromotionsServiceProvider;
use App\Modules\Reviews\Infrastructure\Providers\ReviewsServiceProvider;
use App\Modules\Shared\Infrastructure\Providers\SharedServiceProvider;
use App\Modules\Support\Infrastructure\Providers\SupportServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    IdentityServiceProvider::class,
    CatalogServiceProvider::class,
    CustomizationServiceProvider::class,
    ContentServiceProvider::class,
    CartServiceProvider::class,
    OrderingServiceProvider::class,
    CustomersServiceProvider::class,
    PromotionsServiceProvider::class,
    ReviewsServiceProvider::class,
    PaymentsServiceProvider::class,
    SupportServiceProvider::class,
];
