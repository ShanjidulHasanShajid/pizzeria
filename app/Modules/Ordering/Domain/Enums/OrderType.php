<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\Enums;

/**
 * How the customer gets the order.
 */
enum OrderType: string
{
    case Delivery = 'delivery';
    case Pickup = 'pickup';

    public function label(): string
    {
        return match ($this) {
            self::Delivery => 'Delivery',
            self::Pickup => 'Pickup',
        };
    }

    /** What comes after the kitchen: on the road, or on the counter. */
    public function statusAfterPreparing(): OrderStatus
    {
        return match ($this) {
            self::Delivery => OrderStatus::OutForDelivery,
            self::Pickup => OrderStatus::ReadyForPickup,
        };
    }
}
