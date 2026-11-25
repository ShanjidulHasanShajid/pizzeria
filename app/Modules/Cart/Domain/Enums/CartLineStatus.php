<?php

declare(strict_types=1);

namespace App\Modules\Cart\Domain\Enums;

/**
 * What re-pricing found out about a line (business rules section 7).
 */
enum CartLineStatus: string
{
    case Ok = 'ok';
    case PriceChanged = 'price_changed';
    case Unavailable = 'unavailable';

    /** An unavailable line must be changed or removed before checkout. */
    public function blocksCheckout(): bool
    {
        return $this === self::Unavailable;
    }
}
