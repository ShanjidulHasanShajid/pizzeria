<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

use App\Modules\Catalog\Application\DTOs\CategoryNode;

/**
 * What stands around every shop page: header, navbar, mega menu and footer.
 */
final readonly class SiteLayout
{
    /**
     * @param  list<NavItemView>  $navItems  the visible ones, in order
     * @param  list<CategoryNode>  $menuGroups  the mega menu
     * @param  list<LocationView>  $locations  the active branches, in order
     */
    public function __construct(
        public SiteSettings $settings,
        public array $navItems,
        public array $menuGroups,
        public array $locations,
    ) {}
}
