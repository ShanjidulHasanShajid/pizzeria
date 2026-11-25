<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\Queries;

/**
 * The number in the header. It is on every page, so it must be cheap: no prices, at most one query.
 */
interface CartCountQuery
{
    /** How many items (not lines) are in the cart of this visitor. */
    public function forCurrentVisitor(): int;
}
