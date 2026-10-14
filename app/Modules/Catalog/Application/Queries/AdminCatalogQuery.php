<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Queries;

use App\Modules\Catalog\Application\DTOs\AdminCategoryRow;
use App\Modules\Catalog\Application\DTOs\AdminProductFilter;
use App\Modules\Catalog\Application\DTOs\AdminProductPage;
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
}
