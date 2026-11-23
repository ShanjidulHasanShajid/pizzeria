<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Controllers\Storefront;

use App\Modules\Catalog\Application\DTOs\CategoryPage;
use App\Modules\Catalog\Application\Queries\MenuQuery;
use App\Modules\Catalog\Presentation\Support\ListingData;
use App\Modules\Catalog\Presentation\Support\MenuFilterReader;
use App\Modules\Shared\Application\Ports\FileStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The listing pages: the whole menu, a category, a sub-category, a tag.
 */
final class MenuController
{
    public function __construct(
        private readonly MenuQuery $menu,
        private readonly MenuFilterReader $filters,
        private readonly ListingData $listing,
        private readonly FileStorage $files,
    ) {}

    /** Without a filter: the menu grouped by category. With one: a flat list. */
    public function index(Request $request): View
    {
        $filter = $this->filters->read($request);

        return view('catalog::storefront.menu', [
            'categories' => $this->menu->categories(),
            ...($filter->isActive()
                ? $this->listing->listing($request, $filter)
                : ['sections' => $this->menu->sections(), ...$this->listing->form($request, $filter)]),
        ]);
    }

    public function category(Request $request, string $category): View|RedirectResponse
    {
        $page = $this->menu->category($category);

        if ($page === null) {
            abort(404);
        }

        // A sub-category has one address only: /menu/drinks/coffee.
        return $page->parentSlug === null
            ? $this->categoryView($request, $page)
            : redirect()->route('categories.sub', [$page->parentSlug, $page->slug], 301);
    }

    public function subcategory(Request $request, string $category, string $subcategory): View
    {
        $page = $this->menu->category($subcategory);

        if ($page === null || $page->parentSlug !== $category) {
            abort(404);
        }

        return $this->categoryView($request, $page);
    }

    public function tag(Request $request, string $tag): View
    {
        $badge = $this->menu->tag($tag);

        if ($badge === null) {
            abort(404);
        }

        return view('catalog::storefront.tag', [
            'tag' => $badge,
            ...$this->listing->listing($request, $this->filters->read($request, pageTag: $badge->slug)),
        ]);
    }

    private function categoryView(Request $request, CategoryPage $page): View
    {
        return view('catalog::storefront.category', [
            'category' => $page,
            'image' => $page->imagePath === null ? null : $this->files->url($page->imagePath),
            ...$this->listing->listing($request, $this->filters->read($request, categoryId: $page->id)),
        ]);
    }
}
