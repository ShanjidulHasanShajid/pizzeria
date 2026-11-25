<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\Ports;

use App\Modules\Cart\Application\DTOs\LineQuote;
use App\Modules\Cart\Application\DTOs\LineToPrice;

/**
 * What do these lines cost right now? This module says what it needs; an adapter in
 * Infrastructure asks the pricing engine of the Customization module.
 */
interface LinePricing
{
    /**
     * All lines at once, with the same number of database queries for 1 and for 30 lines.
     *
     * @param  array<int, LineToPrice>  $lines
     * @return array<int, LineQuote> one answer per line, with the same keys
     */
    public function quote(array $lines): array;
}
