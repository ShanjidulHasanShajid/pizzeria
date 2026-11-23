<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Controllers\Storefront;

use App\Modules\Catalog\Application\DTOs\ProductCard;
use App\Modules\Catalog\Application\Queries\StorefrontCatalogQuery;
use App\Modules\Catalog\Presentation\Support\ListingData;
use App\Modules\Catalog\Presentation\Support\MenuFilterReader;
use App\Modules\Catalog\Presentation\Support\ProductCards;
use App\Modules\Shared\Presentation\Support\MoneyFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SearchController
{
    public const SUGGESTIONS = 5;

    /** One letter would match nearly everything. */
    public const SUGGESTION_MIN_LENGTH = 2;

    public function __construct(private readonly MenuFilterReader $filters) {}

    public function index(Request $request, ListingData $listing): View
    {
        $filter = $this->filters->read($request, withSearch: true);

        return view('catalog::storefront.search', [
            'hasTerm' => $filter->search !== '',
            ...($filter->search === '' ? $listing->form($request, $filter) : $listing->listing($request, $filter)),
        ]);
    }

    /** The best matches while the customer types. The route is rate limited. */
    public function suggestions(Request $request, StorefrontCatalogQuery $catalog, ProductCards $cards, MoneyFormatter $money): JsonResponse
    {
        $term = $this->filters->read($request, withSearch: true)->search;
        $found = mb_strlen($term) < self::SUGGESTION_MIN_LENGTH ? [] : $catalog->search($term, self::SUGGESTIONS);

        return response()->json(array_map(function (ProductCard $card) use ($cards, $money): array {
            $shown = $cards->toArray($card);

            return [
                'name' => $shown['name'],
                'url' => route('products.show', $shown['slug']),
                'image' => $shown['image'],
                'price' => ($shown['isFromPrice'] ? 'From ' : '').$money->format($card->fromPrice),
                'isSoldOut' => ! $card->isAvailable,
            ];
        }, $found));
    }
}
