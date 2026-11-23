<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\Ports;

/**
 * The size tiers of the catalog: the columns of the price matrix.
 */
interface SizeTiers
{
    /**
     * @return list<int> ids of the size tiers that are not deleted, in sort order
     */
    public function ids(): array;
}
