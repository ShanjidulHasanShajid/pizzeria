<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Queries;

use App\Modules\Catalog\Application\Queries\StorefrontCatalogQuery;
use App\Modules\Content\Application\DTOs\SiteLayout;
use App\Modules\Content\Application\Queries\LocationQuery;
use App\Modules\Content\Application\Queries\NavigationQuery;
use App\Modules\Content\Application\Queries\SettingsQuery;
use App\Modules\Content\Application\Queries\SiteLayoutQuery;

/**
 * Content from the cache, the category tree from the Catalog module.
 */
final class CatalogSiteLayoutQuery implements SiteLayoutQuery
{
    public function __construct(
        private readonly SettingsQuery $settings,
        private readonly NavigationQuery $navigation,
        private readonly LocationQuery $locations,
        private readonly StorefrontCatalogQuery $catalog,
    ) {}

    public function current(): SiteLayout
    {
        return new SiteLayout(
            $this->settings->current(),
            $this->navigation->visible(),
            $this->catalog->megaMenu(),
            $this->locations->active(),
        );
    }
}
