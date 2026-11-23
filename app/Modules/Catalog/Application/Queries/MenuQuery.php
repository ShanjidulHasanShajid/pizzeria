<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Queries;

use App\Modules\Catalog\Application\DTOs\CategoryNode;
use App\Modules\Catalog\Application\DTOs\CategoryPage;
use App\Modules\Catalog\Application\DTOs\CategorySection;
use App\Modules\Catalog\Application\DTOs\MenuFilter;
use App\Modules\Catalog\Application\DTOs\ProductCardPage;
use App\Modules\Catalog\Application\DTOs\TagBadge;

/**
 * Read side of the menu, category and tag pages. Shows only what a customer may see
 * (see StorefrontCatalogQuery).
 */
interface MenuQuery
{
    /**
     * All active top-level categories with their active sub-categories, also those hidden from the mega menu.
     *
     * @return list<CategoryNode>
     */
    public function categories(): array;

    /**
     * The whole menu: every top-level category that has something to show, with all its products.
     *
     * @return list<CategorySection>
     */
    public function sections(): array;

    /** A visible category or sub-category, or null (the page then answers 404). */
    public function category(string $slug): ?CategoryPage;

    /** An active tag, or null. */
    public function tag(string $slug): ?TagBadge;

    /**
     * The tags to filter by: active dietary and flavour tags. Allergens are warnings, not wishes.
     *
     * @return list<TagBadge>
     */
    public function filterTags(): array;

    public function listing(MenuFilter $filter): ProductCardPage;
}
