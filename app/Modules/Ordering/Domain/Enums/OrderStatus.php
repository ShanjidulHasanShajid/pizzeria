<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\Enums;

/**
 * Where an order is. It walks one step at a time and never back:
 * pending, confirmed, preparing, out for delivery or ready for pickup, delivered.
 * Any order that is not finished can be cancelled.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case OutForDelivery = 'out_for_delivery';
    case ReadyForPickup = 'ready_for_pickup';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /** The next step on the normal way, or null at the end. */
    public function next(OrderType $type): ?self
    {
        return match ($this) {
            self::Pending => self::Confirmed,
            self::Confirmed => self::Preparing,
            self::Preparing => $type->statusAfterPreparing(),
            self::OutForDelivery, self::ReadyForPickup => self::Delivered,
            self::Delivered, self::Cancelled => null,
        };
    }

    public function canTransitionTo(self $new, OrderType $type): bool
    {
        if ($this->isFinal()) {
            return false;
        }

        return $new === self::Cancelled || $new === $this->next($type);
    }

    /** Nothing can happen to the order any more. */
    public function isFinal(): bool
    {
        return $this === self::Delivered || $this === self::Cancelled;
    }

    public function label(OrderType $type): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Preparing => 'Preparing',
            self::OutForDelivery => 'Out for delivery',
            self::ReadyForPickup => 'Ready for pickup',
            self::Delivered => $type === OrderType::Pickup ? 'Picked up' : 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }
}
