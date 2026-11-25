<?php

declare(strict_types=1);

use App\Modules\Ordering\Domain\Entities\DeliveryZone;
use App\Modules\Ordering\Domain\ValueObjects\DeliveryZoneDetails;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * The messages per field, or an empty array when everything was fine.
 *
 * @return array<string, list<string>>
 */
function zoneErrors(string $name, int $locationId, string $fee, string $minimum): array
{
    try {
        DeliveryZoneDetails::fromInput($name, $locationId, $fee, $minimum);
    } catch (ValidationFailed $failure) {
        return $failure->errors();
    }

    return [];
}

it('starts a new zone with a fee of 80 taka', function (): void {
    expect(DeliveryZone::startingFee()->poisha())->toBe(8000);
});

it('reads the form and cleans the name', function (): void {
    $details = DeliveryZoneDetails::fromInput("  Gulshan \n 1 ", 3, '80.50', '300');

    expect($details->name)->toBe('Gulshan 1')
        ->and($details->locationId)->toBe(3)
        ->and($details->fee->poisha())->toBe(8050)
        ->and($details->minimumOrder->poisha())->toBe(30000);
});

it('allows free delivery and no minimum', function (string $minimum): void {
    $details = DeliveryZoneDetails::fromInput('Gulshan', 3, '0', $minimum);

    expect($details->fee->isZero())->toBeTrue()
        ->and($details->minimumOrder->isZero())->toBeTrue();
})->with(['empty' => '', 'spaces' => '  ', 'zero' => '0']);

it('reports all problems together', function (): void {
    expect(array_keys(zoneErrors(' ', 0, '', 'many')))->toBe(['name', 'fee', 'min_order', 'location_id']);
});

it('refuses a wrong amount', function (string $amount): void {
    expect(array_keys(zoneErrors('Gulshan', 3, $amount, '0')))->toBe(['fee'])
        ->and(array_keys(zoneErrors('Gulshan', 3, '80', $amount)))->toBe(['min_order']);
})->with(['negative' => '-1', 'three decimals' => '80.505', 'comma' => '1,000', 'words' => 'eighty', 'too much' => '1000000.01']);

it('accepts the longest name and the highest amount, and not one more', function (): void {
    expect(zoneErrors(str_repeat('ঢ', 100), 3, '1000000', '1000000'))->toBe([])
        ->and(array_keys(zoneErrors(str_repeat('x', 101), 3, '80', '0')))->toBe(['name']);
});

it('changes, switches and moves', function (): void {
    $zone = DeliveryZone::create(DeliveryZoneDetails::fromInput('Gulshan', 3, '80', ''), true, SortOrder::first());

    $zone->change(DeliveryZoneDetails::fromInput('Banani', 4, '60', '500'), true);
    $zone->toggle();
    $zone->moveTo(SortOrder::fromInt(40));

    expect($zone->id())->toBeNull()
        ->and($zone->details()->name)->toBe('Banani')
        ->and($zone->details()->locationId)->toBe(4)
        ->and($zone->isActive())->toBeFalse()
        ->and($zone->sort()->value())->toBe(40);

    $zone->toggle();
    expect($zone->isActive())->toBeTrue();
});
