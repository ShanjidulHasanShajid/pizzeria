<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Queries;

use App\Modules\Catalog\Application\DTOs\CategorySection;
use App\Modules\Catalog\Application\DTOs\CategorySectionRequest;
use App\Modules\Catalog\Application\Queries\StorefrontCatalogQuery;
use App\Modules\Content\Application\DTOs\HomePage;
use App\Modules\Content\Application\Queries\ContentQuery;
use App\Modules\Content\Application\Queries\HomePageQuery;
use App\Modules\Content\Application\Queries\SettingsQuery;
use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Domain\ValueObjects\HomeLayout;
use App\Modules\Content\Domain\ValueObjects\HomeRow;

/**
 * Content from the cache, products from the Catalog module. All tabs and rows are asked for in
 * one call, so the number of database queries does not grow with them.
 */
final class CatalogHomePageQuery implements HomePageQuery
{
    private const PRODUCTS_PER_TAB = 4;

    public function __construct(
        private readonly SettingsQuery $settings,
        private readonly ContentQuery $content,
        private readonly StorefrontCatalogQuery $catalog,
    ) {}

    public function current(): HomePage
    {
        $settings = $this->settings->current();
        $layout = $this->content->homeLayout();
        $sections = $this->catalog->categorySections($this->requests($layout));
        $tabCount = count($layout->tabCategoryIds);

        return new HomePage(
            $settings,
            $this->content->visible(ContentType::HeroSlide),
            $this->content->visible(ContentType::TrustBadge),
            $this->content->visible(ContentType::Banner),
            $this->catalog->hotProducts($settings->hotItemsLimit),
            $this->withProducts(array_slice($sections, 0, $tabCount)),
            $this->withProducts(array_slice($sections, $tabCount)),
            $this->content->visible(ContentType::GalleryImage),
        );
    }

    /**
     * First the tabs, then the rows.
     *
     * @return list<CategorySectionRequest>
     */
    private function requests(HomeLayout $layout): array
    {
        return [
            ...array_map(fn (int $categoryId): CategorySectionRequest => new CategorySectionRequest($categoryId, self::PRODUCTS_PER_TAB), $layout->tabCategoryIds),
            ...array_map(fn (HomeRow $row): CategorySectionRequest => new CategorySectionRequest($row->categoryId, HomeRow::MAX_PRODUCTS, $row->productIds), $layout->rows),
        ];
    }

    /**
     * Leaves out categories that are hidden now or have nothing to show.
     *
     * @param  list<?CategorySection>  $sections
     * @return list<CategorySection>
     */
    private function withProducts(array $sections): array
    {
        $kept = [];

        foreach ($sections as $section) {
            if ($section !== null && $section->products !== []) {
                $kept[] = $section;
            }
        }

        return $kept;
    }
}
