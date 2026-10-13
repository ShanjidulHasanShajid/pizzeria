<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entities\KitchenStation;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

it('creates an active station without an id', function (): void {
    $station = KitchenStation::create('  Oven ', SortOrder::first());

    expect($station->id())->toBeNull()
        ->and($station->name())->toBe('Oven')
        ->and($station->sort()->value())->toBe(10)
        ->and($station->isActive())->toBeTrue();
});

it('rebuilds a saved station with its id', function (): void {
    $station = KitchenStation::reconstitute(7, 'Fryer', SortOrder::fromInt(20), false);

    expect($station->id())->toBe(7)
        ->and($station->isActive())->toBeFalse();
});

it('can be renamed, moved, deactivated and activated', function (): void {
    $station = KitchenStation::create('Oven', SortOrder::first());

    $station->rename('Pizza Oven');
    $station->moveTo(SortOrder::fromInt(30));
    $station->deactivate();

    expect($station->name())->toBe('Pizza Oven')
        ->and($station->sort()->value())->toBe(30)
        ->and($station->isActive())->toBeFalse();

    $station->activate();

    expect($station->isActive())->toBeTrue();
});

it('rejects an empty or too long name', function (string $name): void {
    expect(fn () => KitchenStation::create($name, SortOrder::first()))->toThrow(InvalidValue::class);
})->with([
    'empty' => '',
    'only spaces' => '   ',
    'too long' => str_repeat('x', 101),
]);
