<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Enums;

/**
 * The orders a customer can choose on a listing page. The value stands in the address (?sort=).
 */
enum ProductSort: string
{
    case Recommended = 'recommended';
    case Newest = 'newest';
    case PriceLowToHigh = 'price-asc';
    case PriceHighToLow = 'price-desc';

    public function label(): string
    {
        return match ($this) {
            self::Recommended => 'Recommended',
            self::Newest => 'Newest',
            self::PriceLowToHigh => 'Price: low to high',
            self::PriceHighToLow => 'Price: high to low',
        };
    }
}
