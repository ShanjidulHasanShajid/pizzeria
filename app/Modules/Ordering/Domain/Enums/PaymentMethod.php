<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\Enums;

/**
 * How the customer pays. Online payment comes in Phase 21.
 */
enum PaymentMethod: string
{
    case CashOnDelivery = 'cash_on_delivery';

    public function label(OrderType $type): string
    {
        return match ($this) {
            self::CashOnDelivery => $type === OrderType::Pickup ? 'Cash at pickup' : 'Cash on delivery',
        };
    }
}
