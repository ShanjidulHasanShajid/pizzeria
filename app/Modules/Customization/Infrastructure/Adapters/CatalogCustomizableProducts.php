<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Adapters;

use App\Modules\Catalog\Application\Queries\AdminCatalogQuery;
use App\Modules\Customization\Application\Ports\CustomizableProducts;

final class CatalogCustomizableProducts implements CustomizableProducts
{
    public function __construct(private readonly AdminCatalogQuery $catalog) {}

    public function exists(int $productId): bool
    {
        return $this->catalog->isCustomizableProduct($productId);
    }

    public function idsInCategory(int $categoryId): ?array
    {
        return $this->catalog->customizableProductIds($categoryId);
    }

    public function categoryNames(): array
    {
        $names = [];

        foreach ($this->catalog->categoryTable() as $row) {
            $names[$row->id] = $row->parentName === null ? $row->name : "{$row->parentName} > {$row->name}";
        }

        return $names;
    }
}
