<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure\Providers;

use App\Modules\Ordering\Application\Ports\Branches;
use App\Modules\Ordering\Application\Queries\AdminDeliveryZoneQuery;
use App\Modules\Ordering\Application\Queries\DeliveryZoneUsageQuery;
use App\Modules\Ordering\Domain\Repositories\DeliveryZoneRepository;
use App\Modules\Ordering\Infrastructure\Adapters\ContentBranches;
use App\Modules\Ordering\Infrastructure\Persistence\Repositories\EloquentDeliveryZoneRepository;
use App\Modules\Ordering\Infrastructure\Queries\EloquentAdminDeliveryZoneQuery;
use App\Modules\Ordering\Infrastructure\Queries\EloquentDeliveryZoneUsageQuery;
use App\Modules\Shared\Infrastructure\Providers\ModuleServiceProvider;

final class OrderingServiceProvider extends ModuleServiceProvider
{
    /**
     * Interface => implementation pairs for this module.
     * Laravel registers this array automatically.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        DeliveryZoneRepository::class => EloquentDeliveryZoneRepository::class,
        AdminDeliveryZoneQuery::class => EloquentAdminDeliveryZoneQuery::class,
        DeliveryZoneUsageQuery::class => EloquentDeliveryZoneUsageQuery::class,
        Branches::class => ContentBranches::class,
    ];

    protected function moduleName(): string
    {
        return 'Ordering';
    }
}
