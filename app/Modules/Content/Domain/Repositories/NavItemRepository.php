<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\Repositories;

use App\Modules\Content\Domain\Enums\NavRoute;
use App\Modules\Content\Domain\ValueObjects\NavItem;

interface NavItemRepository
{
    /**
     * One item for every whitelisted page, in sort order. Pages never saved have their default.
     *
     * @return list<NavItem>
     */
    public function all(): array;

    public function get(NavRoute $route): NavItem;

    public function save(NavItem $item): void;
}
