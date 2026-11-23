<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Queries;

use App\Modules\Catalog\Application\DTOs\AdminCategoryDetail;
use App\Modules\Catalog\Application\DTOs\AdminCategoryFilter;
use App\Modules\Catalog\Application\DTOs\AdminCategoryRow;
use App\Modules\Catalog\Application\DTOs\AdminProductDetail;
use App\Modules\Catalog\Application\DTOs\AdminProductFilter;
use App\Modules\Catalog\Application\DTOs\AdminProductPage;
use App\Modules\Catalog\Application\DTOs\KitchenStationRow;
use App\Modules\Catalog\Application\DTOs\SizeTierRow;
use App\Modules\Catalog\Application\DTOs\TagRow;
use App\Modules\Catalog\Application\DTOs\TrashedCatalogItem;

/**
 * Read side of the catalog for the admin screens. Returns read DTOs, never entities.
 */
interface AdminCatalogQuery
{
    /**
     * Without a filter: every category that is not deleted, each top-level category followed
     * by its sub-categories, in sort order. With a search or a status: a flat list of the
     * matching categories (status "trashed" lists the Trash).
     *
     * @return list<AdminCategoryRow>
     */
    public function categoryTable(AdminCategoryFilter $filter = new AdminCategoryFilter): array;

    public function productTable(AdminProductFilter $filter): AdminProductPage;

    /**
     * Deleted kitchen stations, size tiers, tags, categories and products, newest deletion first.
     *
     * @return list<TrashedCatalogItem>
     */
    public function trashedItems(): array;

    /**
     * Kitchen stations that are not deleted, in sort order, with how many categories use each.
     *
     * @return list<KitchenStationRow>
     */
    public function kitchenStationTable(): array;

    /** One station that is not deleted, or null. */
    public function kitchenStation(int $id): ?KitchenStationRow;

    /**
     * Size tiers that are not deleted, in sort order, with how many variants use each.
     *
     * @return list<SizeTierRow>
     */
    public function sizeTierTable(): array;

    /** One size tier that is not deleted, or null. */
    public function sizeTier(int $id): ?SizeTierRow;

    /**
     * Tags that are not deleted, in sort order, with how many products have each.
     *
     * @return list<TagRow>
     */
    public function tagTable(): array;

    /** One tag that is not deleted, or null. */
    public function tag(int $id): ?TagRow;

    /** One category that is not deleted, for the edit form, or null. */
    public function category(int $id): ?AdminCategoryDetail;

    /**
     * Top-level categories that are not deleted, for the "Parent" choice: id => name, in sort order.
     *
     * @return array<int, string>
     */
    public function topLevelCategoryOptions(): array;

    /** One product that is not deleted, with variants, images and tag ids, for the edit form; or null. */
    public function product(int $id): ?AdminProductDetail;

    /** True for a product that is not deleted and may have option groups. Combos use slots instead. */
    public function isCustomizableProduct(int $productId): bool;

    /**
     * The products of a category and its sub-categories that may have option groups,
     * hidden ones included; or null when there is no such category.
     *
     * @return list<int>|null
     */
    public function customizableProductIds(int $categoryId): ?array;
}
