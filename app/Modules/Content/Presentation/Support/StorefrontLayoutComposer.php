<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Support;

use App\Modules\Content\Application\Queries\SiteLayoutQuery;
use App\Modules\Shared\Application\Ports\FileStorage;
use Illuminate\Contracts\View\View;

/**
 * Laravel runs this every time the storefront layout is rendered (registered in AppServiceProvider).
 * The layout then hands the parts to the header, navbar and footer.
 */
final class StorefrontLayoutComposer
{
    public function __construct(
        private readonly SiteLayoutQuery $layout,
        private readonly FileStorage $files,
    ) {}

    public function compose(View $view): void
    {
        $site = $this->layout->current();
        $logo = $site->settings->logoPath;
        $favicon = $site->settings->faviconPath;

        $view->with([
            'site' => $site,
            'logoUrl' => $logo === null ? null : $this->files->url($logo),
            'faviconUrl' => $favicon === null ? null : $this->files->url($favicon),
        ]);
    }
}
