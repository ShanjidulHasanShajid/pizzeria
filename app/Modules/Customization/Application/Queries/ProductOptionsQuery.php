<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\Queries;

use App\Modules\Customization\Application\DTOs\ProductOptionGroupView;

/**
 * Read side of the options for the shop pages.
 */
interface ProductOptionsQuery
{
    /**
     * The active groups attached to a product, in the product's order, each with its rules and options.
     * Deleted groups and deleted options are left out.
     *
     * @return list<ProductOptionGroupView>
     */
    public function forProduct(int $productId): array;

    /**
     * Those of the products that have at least one active group, with one query for all of them.
     *
     * @param  list<int>  $productIds
     * @return list<int>
     */
    public function productIdsWithOptions(array $productIds): array;
}
