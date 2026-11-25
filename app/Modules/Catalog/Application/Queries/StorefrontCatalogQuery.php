<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Queries;

use App\Modules\Catalog\Application\DTOs\CategoryNode;
use App\Modules\Catalog\Application\DTOs\CategorySection;
use App\Modules\Catalog\Application\DTOs\CategorySectionRequest;
use App\Modules\Catalog\Application\DTOs\ProductCard;
use App\Modules\Catalog\Application\DTOs\ProductDetail;
use App\Modules\Catalog\Application\DTOs\ProductPricingFacts;

/**
 * Read side of the catalog for the shop pages. Returns read DTOs, never entities.
 *
 * Every method except the two for pricing shows only what a customer may see: published products that are not deleted,
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

    /**
     * Several categories with some of their products at once, always with the same number of
     * database queries. One answer per request, in the same order; null for a category that is
     * hidden, deleted or unknown. Products of active sub-categories count too.
     *
     * @param  list<CategorySectionRequest>  $requests
     * @return list<?CategorySection>
     */
    public function categorySections(array $requests): array;

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
     * Products whose name or short description contains $term; matches in the name come first.
     *
     * @return list<ProductCard>
     */
    public function search(string $term, int $limit): array;

    /**
     * What pricing needs to know about a product that is not deleted, or null. A hidden product is
     * returned with isVisible = false, so the caller can say why it cannot be ordered.
     */
    public function pricingFacts(int $productId): ?ProductPricingFacts;

    /**
     * The same for several products at once, always with the same number of database queries.
     * Unknown and deleted products are left out.
     *
     * @param  list<int>  $productIds
     * @return array<int, ProductPricingFacts> product id => facts
     */
    public function pricingFactsFor(array $productIds): array;
}
