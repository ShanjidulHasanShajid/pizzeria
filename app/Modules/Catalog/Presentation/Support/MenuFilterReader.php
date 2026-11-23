<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Support;

use App\Modules\Catalog\Application\DTOs\MenuFilter;
use App\Modules\Catalog\Domain\Enums\ProductSort;
use App\Modules\Shared\Domain\ValueObjects\Money;
use Illuminate\Http\Request;

/**
 * Reads the filter from the address: ?tags[]=spicy&min=200&max=500&available=1&sort=price-asc&page=2.
 * Everything that makes no sense is ignored: a listing page never shows an error for its address.
 */
final class MenuFilterReader
{
    private const MAX_TAGS = 10;

    private const MAX_SEARCH_LENGTH = 60;

    public function read(Request $request, ?int $categoryId = null, ?string $pageTag = null, bool $withSearch = false): MenuFilter
    {
        return new MenuFilter(
            categoryId: $categoryId,
            pageTag: $pageTag,
            tagSlugs: $this->tags($request->query('tags'), $pageTag),
            minPrice: $this->price($request->query('min')),
            maxPrice: $this->price($request->query('max')),
            onlyAvailable: $request->query('available') === '1',
            sort: ProductSort::tryFrom($this->text($request->query('sort'))) ?? ProductSort::Recommended,
            page: $this->page($request->query('page')),
            search: $withSearch ? $this->search($request->query('q')) : '',
        );
    }

    /**
     * @return list<string>
     */
    private function tags(mixed $value, ?string $pageTag): array
    {
        $tags = [];

        foreach (is_array($value) ? $value : [] as $slug) {
            if (is_string($slug) && $slug !== $pageTag && preg_match('/^[a-z0-9-]{1,120}$/D', $slug) === 1) {
                $tags[$slug] = $slug;
            }
        }

        return array_slice(array_values($tags), 0, self::MAX_TAGS);
    }

    /** Whole taka. */
    private function price(mixed $value): ?Money
    {
        $text = $this->text($value);

        return preg_match('/^\d{1,6}$/D', $text) === 1 ? Money::fromTaka((int) $text) : null;
    }

    private function page(mixed $value): int
    {
        $text = $this->text($value);

        return preg_match('/^\d{1,6}$/D', $text) === 1 ? max(1, (int) $text) : 1;
    }

    /** Broken UTF-8 gives "". */
    private function search(mixed $value): string
    {
        $words = preg_replace('/\s+/u', ' ', $this->text($value)) ?? '';

        return trim(mb_substr(trim($words), 0, self::MAX_SEARCH_LENGTH));
    }

    private function text(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
