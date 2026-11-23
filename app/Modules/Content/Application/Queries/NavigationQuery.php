<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\Queries;

use App\Modules\Content\Application\DTOs\NavItemView;

interface NavigationQuery
{
    /**
     * What the navbar shows, in order.
     *
     * @return list<NavItemView>
     */
    public function visible(): array;

    /**
     * Every whitelisted page, for the admin screen.
     *
     * @return list<NavItemView>
     */
    public function all(): array;
}
