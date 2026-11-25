<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Queries;

use App\Modules\Catalog\Application\DTOs\ProductSummary;

interface ProductSummaryQuery
{
    /**
     * Also hidden products and products in the Trash (isVisible = false). Unknown ids are left out.
     *
     * @param  list<int>  $ids
     * @return array<int, ProductSummary> product id => summary
     */
    public function byIds(array $ids): array;
}
