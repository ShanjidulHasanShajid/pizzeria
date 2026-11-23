<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\Queries;

/**
 * Do option prices use a size tier? The Catalog module asks before it deletes one.
 */
interface OptionPriceUsageQuery
{
    /** True when an option that is not deleted and is priced per size has a price for this size. */
    public function isPricedAt(int $sizeTierId): bool;

    /** True when any price row points to this size, also leftovers of deleted or flat-priced options. */
    public function hasAnyPriceAt(int $sizeTierId): bool;
}
