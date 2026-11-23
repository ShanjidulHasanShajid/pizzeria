<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\Queries;

use App\Modules\Customization\Application\DTOs\ProductConfiguration;

interface ProductConfigurationQuery
{
    /** What can be chosen for this product, or null when customers cannot see it. */
    public function forProduct(int $productId): ?ProductConfiguration;
}
