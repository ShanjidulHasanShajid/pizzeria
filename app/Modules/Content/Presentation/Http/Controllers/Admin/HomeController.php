<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Http\Controllers\Admin;

use App\Modules\Content\Application\Ports\HomeCatalog;
use App\Modules\Content\Application\Queries\ContentQuery;
use App\Modules\Content\Application\UseCases\Home\SaveHomeLayout;
use App\Modules\Content\Domain\ValueObjects\HomeLayout;
use App\Modules\Content\Domain\ValueObjects\HomeRow;
use App\Modules\Content\Presentation\Http\Controllers\Admin\Concerns\RunsContentActions;
use App\Modules\Content\Presentation\Http\Requests\HomeLayoutRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * The home page layout: showcase tabs and the three category rows. Routes require "manage-content".
 */
final class HomeController
{
    use RunsContentActions;

    public function edit(ContentQuery $query, HomeCatalog $catalog): View
    {
        $layout = $query->homeLayout();
        $categories = $catalog->categoryNames();

        return view('content::admin.home.edit', [
            'layout' => $layout,
            'categories' => $categories,
            // For the rows: the products of every category that is chosen now.
            'products' => array_map(
                fn (HomeRow $row): array => $catalog->productNames($row->categoryId),
                $layout->rows,
            ),
            'maxTabs' => HomeLayout::MAX_TABS,
            'maxRows' => HomeLayout::MAX_ROWS,
            'maxProducts' => HomeRow::MAX_PRODUCTS,
        ]);
    }

    public function update(HomeLayoutRequest $request, SaveHomeLayout $saveLayout): RedirectResponse
    {
        $this->runAction(fn () => $saveLayout->handle($request->toInput()));

        return redirect()->route('admin.home.edit')->with('success', 'Home page layout saved.');
    }
}
