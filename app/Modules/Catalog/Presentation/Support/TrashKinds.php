<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Support;

use App\Modules\Catalog\Application\DTOs\TrashedCatalogItem;

/**
 * What the Trash screen needs to know about each kind of deleted item:
 * the tab label, the label of one item, the start of its route names
 * (".restore" and ".force-destroy" are added), and what "Delete forever" also removes.
 */
final class TrashKinds
{
    /** @var array<string, array{tab: string, one: string, route: string, forever: string}> */
    public const KINDS = [
        'product' => [
            'tab' => 'Products', 'one' => 'Product', 'route' => 'admin.products',
            'forever' => 'The product, its variants and its images are deleted for good. Old orders keep their own copy of the name and price.',
        ],
        'category' => [
            'tab' => 'Categories', 'one' => 'Category', 'route' => 'admin.categories',
            'forever' => 'The category and its image are deleted for good. This only works when no product or sub-category belongs to it, also none in the Trash.',
        ],
        'tag' => [
            'tab' => 'Tags', 'one' => 'Tag', 'route' => 'admin.tags',
            'forever' => 'The tag is deleted for good and removed from every product that had it.',
        ],
        'size_tier' => [
            'tab' => 'Size tiers', 'one' => 'Size tier', 'route' => 'admin.size-tiers',
            'forever' => 'The size is deleted for good. This only works when no variant uses it, also removed variants and products in the Trash.',
        ],
        'kitchen_station' => [
            'tab' => 'Kitchen stations', 'one' => 'Kitchen station', 'route' => 'admin.kitchen-stations',
            'forever' => 'The station is deleted for good. This only works when no category uses it, also none in the Trash.',
        ],
    ];

    /**
     * The tabs above the list: "all" first, then one per kind, each with how many items it holds.
     *
     * @param  list<TrashedCatalogItem>  $items
     * @return array<string, string> tab key => label, for example "Products (3)"
     */
    public static function tabs(array $items): array
    {
        $counts = array_count_values(array_map(fn (TrashedCatalogItem $item): string => $item->kind, $items));
        $tabs = ['all' => 'All ('.count($items).')'];

        foreach (self::KINDS as $kind => $labels) {
            $tabs[$kind] = $labels['tab'].' ('.($counts[$kind] ?? 0).')';
        }

        return $tabs;
    }
}
