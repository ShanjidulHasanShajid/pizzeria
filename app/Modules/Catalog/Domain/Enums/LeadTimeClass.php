<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Enums;

/**
 * How a product is prepared: cooked when ordered, or ordered in advance.
 */
enum LeadTimeClass: string
{
    case MadeToOrder = 'made_to_order';
    case SameDay = 'same_day';
    case NextDay = 'next_day';

    public function label(): string
    {
        return match ($this) {
            self::MadeToOrder => 'Made to order',
            self::SameDay => 'Same day',
            self::NextDay => 'Next day',
        };
    }
}
