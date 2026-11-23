<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Support;

use App\Modules\Catalog\Application\DTOs\TrashedCatalogItem;
use App\Modules\Catalog\Application\Queries\AdminCatalogQuery;
use App\Modules\Shared\Presentation\Support\TrashItem;
use App\Modules\Shared\Presentation\Support\TrashKind;
use App\Modules\Shared\Presentation\Support\TrashSource;

/**
 * What the Catalog module adds to the Trash.
 */
final class CatalogTrash implements TrashSource
{
    public function __construct(private readonly AdminCatalogQuery $catalog) {}

    public function kinds(): array
    {
        return [
            $this->kind('product', 'Products', 'Product', 'admin.products', 'The product, its variants and its images are deleted for good. Old orders keep their own copy of the name and price.'),
            $this->kind('category', 'Categories', 'Category', 'admin.categories', 'The category and its image are deleted for good. This only works when no product or sub-category belongs to it, also none in the Trash.'),
            $this->kind('tag', 'Tags', 'Tag', 'admin.tags', 'The tag is deleted for good and removed from every product that had it.'),
            $this->kind('size_tier', 'Size tiers', 'Size tier', 'admin.size-tiers', 'The size is deleted for good. This only works when no variant and no option price uses it, also removed ones and products in the Trash.'),
            $this->kind('kitchen_station', 'Kitchen stations', 'Kitchen station', 'admin.kitchen-stations', 'The station is deleted for good. This only works when no category uses it, also none in the Trash.'),
        ];
    }

    public function items(): array
    {
        return array_map(
            fn (TrashedCatalogItem $item): TrashItem => new TrashItem($item->kind, $item->id, $item->name, $item->deletedAt),
            $this->catalog->trashedItems(),
        );
    }

    private function kind(string $key, string $tab, string $one, string $route, string $forever): TrashKind
    {
        return new TrashKind($key, $tab, $one, $route, $forever, 'manage-catalog');
    }
}
