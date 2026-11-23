<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Support;

use App\Modules\Catalog\Application\DTOs\MenuFilter;
use App\Modules\Catalog\Application\Queries\MenuQuery;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * The data every listing page shows (views _listing and _filters), and its canonical address.
 */
final class ListingData
{
    public function __construct(private readonly MenuQuery $menu) {}

    /**
     * Cards and filter form. The paginator keeps the filter in its links.
     *
     * @return array<string, mixed>
     */
    public function listing(Request $request, MenuFilter $filter): array
    {
        $page = $this->menu->listing($filter);

        return [
            ...$this->form($request, $filter),
            'canonical' => $this->canonical($request, $filter, $page->page),
            'pageNumber' => $page->page,
            'products' => new LengthAwarePaginator($page->items, $page->total, $page->perPage, $page->page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]),
        ];
    }

    /**
     * The filter form alone.
     *
     * @return array<string, mixed>
     */
    public function form(Request $request, MenuFilter $filter): array
    {
        return [
            'filter' => $filter,
            'filterTags' => $this->menu->filterTags(),
            // This page without a filter; a search keeps its words.
            'pageUrl' => $filter->search === '' ? $request->url() : $request->url().'?'.http_build_query(['q' => $filter->search]),
            'canonical' => $request->url(),
            'pageNumber' => 1,
        ];
    }

    /** A filtered list is the same page in another order; page 2 of the whole list is a page of its own. */
    private function canonical(Request $request, MenuFilter $filter, int $page): string
    {
        return $filter->isActive() || $page === 1 ? $request->url() : $request->url().'?page='.$page;
    }
}
