<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Adapters;

use App\Modules\Catalog\Application\Ports\ProductOptions;
use App\Modules\Customization\Application\Queries\ProductOptionsQuery;

final class CustomizationProductOptions implements ProductOptions
{
    public function __construct(private readonly ProductOptionsQuery $options) {}

    public function idsWithOptions(array $productIds): array
    {
        return $this->options->productIdsWithOptions($productIds);
    }
}
