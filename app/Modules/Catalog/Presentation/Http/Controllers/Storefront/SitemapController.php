<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Controllers\Storefront;

use App\Modules\Catalog\Application\DTOs\SitemapEntry;
use App\Modules\Catalog\Application\Queries\SitemapQuery;
use Illuminate\Http\Response;

/**
 * /sitemap.xml: the pages search engines should know. Placeholder pages are left out (they say "noindex");
 * each phase that builds such a page adds it to PAGES.
 */
final class SitemapController
{
    /** Route names of pages without parameters. */
    private const PAGES = ['home', 'menu.index'];

    public function __invoke(SitemapQuery $query): Response
    {
        $urls = array_map(fn (string $page): array => ['loc' => route($page), 'lastmod' => null], self::PAGES);

        foreach ($query->entries() as $entry) {
            $urls[] = ['loc' => $this->address($entry), 'lastmod' => $entry->changedAt?->format('Y-m-d')];
        }

        return response()->view('catalog::storefront.sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function address(SitemapEntry $entry): string
    {
        return match (true) {
            $entry->kind === 'product' => route('products.show', $entry->slug),
            $entry->parentSlug !== null => route('categories.sub', [$entry->parentSlug, $entry->slug]),
            default => route('categories.show', $entry->slug),
        };
    }
}
