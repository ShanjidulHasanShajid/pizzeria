<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Adapters;

use App\Modules\Catalog\Application\Ports\SizeTierPrices;
use App\Modules\Customization\Application\Queries\OptionPriceUsageQuery;

final class CustomizationSizeTierPrices implements SizeTierPrices
{
    public function __construct(private readonly OptionPriceUsageQuery $optionPrices) {}

    public function usesSizeTier(int $sizeTierId): bool
    {
        return $this->optionPrices->isPricedAt($sizeTierId);
    }

    public function usesSizeTierIncludingTrash(int $sizeTierId): bool
    {
        return $this->optionPrices->hasAnyPriceAt($sizeTierId);
    }
}
