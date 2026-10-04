<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Providers;

use App\Modules\Shared\Infrastructure\Providers\ModuleServiceProvider;

final class CatalogServiceProvider extends ModuleServiceProvider
{
    /**
     * Interface => implementation pairs for this module.
     * Laravel registers this array automatically.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [];

    protected function moduleName(): string
    {
        return 'Catalog';
    }
}
