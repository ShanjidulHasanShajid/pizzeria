<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Queries;

use App\Modules\Catalog\Application\DTOs\CategoryNode;
use App\Modules\Catalog\Application\DTOs\ProductCard;
use App\Modules\Catalog\Application\DTOs\ProductDetail;

/**
 * Read side of the catalog for the shop pages. Returns read DTOs, never entities.
 *
 * Every method shows only what a customer may see: published products that are not deleted,
 * in active categories that are not deleted. Sold-out products are included (they show
 * the disabled "Sold out" state). Deleted variants never appear.
 */
interface StorefrontCatalogQuery
{
    /**
     * Cards for a category page, in sort order. For a top-level category this includes
     * the products of its active sub-categories.
     *
     * @return list<ProductCard>
     */
    public function productCardsForCategory(int $categoryId): array;

    /**
     * Products flagged "Hot", in sort order.
     *
     * @return list<ProductCard>
     */
    public function hotProducts(int $limit): array;

    /**
     * Products for one tab of the home page showcase.
     *
     * @return list<ProductCard>
     */
    public function showcaseProducts(int $categoryId, int $limit): array;

    /** The product page, or null (the page then answers 404). */
    public function productDetail(string $slug): ?ProductDetail;

    /**
     * Other products from the same category, without the product itself.
     *
     * @return list<ProductCard>
     */
    public function relatedProducts(int $productId, int $limit): array;

    /**
     * Active top-level categories shown in the mega menu, each with its active sub-categories.
     *
     * @return list<CategoryNode>
     */
    public function megaMenu(): array;

    /**
     * Products whose name or short description contains $term.
     *
     * @return list<ProductCard>
     */
    public function search(string $term, int $limit): array;
}
