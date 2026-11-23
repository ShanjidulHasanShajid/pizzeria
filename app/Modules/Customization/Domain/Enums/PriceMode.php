<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\Enums;

/**
 * How an option is priced: one price for every size ("Olives ৳60"),
 * or a price per size tier ("Stuffed crust ৳100 / ৳150 / ৳200").
 */
enum PriceMode: string
{
    case Flat = 'flat';
    case PerSize = 'per_size';

    public function label(): string
    {
        return match ($this) {
            self::Flat => 'Same price for every size',
            self::PerSize => 'Price per size',
        };
    }
}
