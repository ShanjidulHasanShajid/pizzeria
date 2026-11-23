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
}
