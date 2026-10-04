<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Providers;

use App\Modules\Shared\Infrastructure\Providers\ModuleServiceProvider;

final class ContentServiceProvider extends ModuleServiceProvider
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
        return 'Content';
    }
}
