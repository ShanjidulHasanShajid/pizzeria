<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

use App\Modules\Content\Domain\Enums\NavRoute;

final readonly class ReorderNavItemsInput
{
    /**
     * @param  list<NavRoute>  $routes  all of them, in their new order
     */
    public function __construct(public array $routes) {}
}
