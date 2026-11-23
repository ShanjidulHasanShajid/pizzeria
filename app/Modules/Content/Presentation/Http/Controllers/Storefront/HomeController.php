<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Http\Controllers\Storefront;

use App\Modules\Content\Application\Queries\HomePageQuery;
use App\Modules\Content\Presentation\Support\HomePresenter;
use Illuminate\Contracts\View\View;

final class HomeController
{
    public function __invoke(HomePageQuery $query, HomePresenter $presenter): View
    {
        return view('content::storefront.home', $presenter->present($query->current()));
    }
}
