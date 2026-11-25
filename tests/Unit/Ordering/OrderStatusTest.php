<?php

declare(strict_types=1);

use App\Modules\Ordering\Domain\Enums\OrderStatus;
use App\Modules\Ordering\Domain\Enums\OrderType;
use App\Modules\Ordering\Domain\Enums\PaymentMethod;
use App\Modules\Ordering\Domain\Enums\PaymentStatus;

it('walks a delivery from pending to delivered', function (): void {
    $way = [];

    for ($status = OrderStatus::Pending; $status !== null; $status = $status->next(OrderType::Delivery)) {
        $way[] = $status->value;
    }

    expect($way)->toBe(['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered']);
});

it('walks a pickup from pending to delivered', function (): void {
    $way = [];

    for ($status = OrderStatus::Pending; $status !== null; $status = $status->next(OrderType::Pickup)) {
        $way[] = $status->value;
    }

    expect($way)->toBe(['pending', 'confirmed', 'preparing', 'ready_for_pickup', 'delivered']);
});

it('allows exactly the next step and cancelling', function (OrderType $type, string $from, array $allowed): void {
    $current = OrderStatus::from($from);
    $possible = array_filter(OrderStatus::cases(), fn (OrderStatus $new): bool => $current->canTransitionTo($new, $type));

    expect(array_map(fn (OrderStatus $status): string => $status->value, array_values($possible)))->toBe($allowed);
})->with([
    'delivery, pending' => [OrderType::Delivery, 'pending', ['confirmed', 'cancelled']],
    'delivery, confirmed' => [OrderType::Delivery, 'confirmed', ['preparing', 'cancelled']],
    'delivery, preparing' => [OrderType::Delivery, 'preparing', ['out_for_delivery', 'cancelled']],
    'delivery, on the road' => [OrderType::Delivery, 'out_for_delivery', ['delivered', 'cancelled']],
    'delivery, delivered' => [OrderType::Delivery, 'delivered', []],
    'delivery, cancelled' => [OrderType::Delivery, 'cancelled', []],
    'pickup, preparing' => [OrderType::Pickup, 'preparing', ['ready_for_pickup', 'cancelled']],
    'pickup, ready' => [OrderType::Pickup, 'ready_for_pickup', ['delivered', 'cancelled']],
    'pickup, picked up' => [OrderType::Pickup, 'delivered', []],
]);

it('knows that only delivered and cancelled orders are finished', function (): void {
    $final = array_filter(OrderStatus::cases(), fn (OrderStatus $status): bool => $status->isFinal());

    expect(array_values($final))->toBe([OrderStatus::Delivered, OrderStatus::Cancelled]);
});

it('uses words that fit the order type', function (): void {
    expect(OrderStatus::Delivered->label(OrderType::Delivery))->toBe('Delivered')
        ->and(OrderStatus::Delivered->label(OrderType::Pickup))->toBe('Picked up')
        ->and(OrderStatus::OutForDelivery->label(OrderType::Delivery))->toBe('Out for delivery')
        ->and(PaymentMethod::CashOnDelivery->label(OrderType::Delivery))->toBe('Cash on delivery')
        ->and(PaymentMethod::CashOnDelivery->label(OrderType::Pickup))->toBe('Cash at pickup')
        ->and(OrderType::Pickup->label())->toBe('Pickup')
        ->and(PaymentStatus::Unpaid->label())->toBe('Unpaid');
});

it('keeps the values that are saved in the database', function (): void {
    expect(array_column(OrderType::cases(), 'value'))->toBe(['delivery', 'pickup'])
        ->and(array_column(PaymentMethod::cases(), 'value'))->toBe(['cash_on_delivery'])
        ->and(array_column(PaymentStatus::cases(), 'value'))->toBe(['unpaid', 'paid', 'refunded']);
});
