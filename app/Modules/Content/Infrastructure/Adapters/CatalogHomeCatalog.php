<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Adapters;

use App\Modules\Catalog\Application\Queries\AdminCatalogQuery;
use App\Modules\Catalog\Application\Queries\StorefrontCatalogQuery;
use App\Modules\Content\Application\Ports\HomeCatalog;

final class CatalogHomeCatalog implements HomeCatalog
{
    public function __construct(
        private readonly AdminCatalogQuery $admin,
        private readonly StorefrontCatalogQuery $shop,
    ) {}

    public function categoryNames(): array
    {
        $names = [];

        foreach ($this->admin->categoryTable() as $row) {
            $names[$row->id] = $row->parentName === null ? $row->name : "{$row->parentName} > {$row->name}";
        }

        return $names;
    }

    public function productNames(int $categoryId): array
    {
        $names = [];

        foreach ($this->shop->productCardsForCategory($categoryId) as $card) {
            $names[$card->id] = $card->name;
        }

        return $names;
    }
}
