<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Queries;

use App\Modules\Catalog\Application\DTOs\AdminCategoryRow;
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
     * Every category that is not deleted: each top-level category followed by its
     * sub-categories, in sort order.
     *
     * @return list<AdminCategoryRow>
     */
    public function categoryTable(): array;

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
}
