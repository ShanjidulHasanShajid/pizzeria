<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Ports;

/**
 * Do prices outside the catalog (option prices) use a size tier? Asked before one is deleted.
 */
interface SizeTierPrices
{
    /** True when something that is for sale or could be has a price for this size. */
    public function usesSizeTier(int $sizeTierId): bool;

    /** True when any price row points to this size, also leftovers: then it cannot be deleted for good. */
    public function usesSizeTierIncludingTrash(int $sizeTierId): bool;
}
