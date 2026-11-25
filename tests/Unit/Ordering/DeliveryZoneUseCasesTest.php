<?php

declare(strict_types=1);

use App\Modules\Ordering\Application\DTOs\Branch;
use App\Modules\Ordering\Application\DTOs\DeliveryZoneIdInput;
use App\Modules\Ordering\Application\DTOs\ReorderDeliveryZonesInput;
use App\Modules\Ordering\Application\DTOs\SaveDeliveryZoneInput;
use App\Modules\Ordering\Application\Services\DeliveryZoneInputReader;
use App\Modules\Ordering\Application\UseCases\DeliveryZones\CreateDeliveryZone;
use App\Modules\Ordering\Application\UseCases\DeliveryZones\DeleteDeliveryZone;
use App\Modules\Ordering\Application\UseCases\DeliveryZones\ForceDeleteDeliveryZone;
use App\Modules\Ordering\Application\UseCases\DeliveryZones\ReorderDeliveryZones;
use App\Modules\Ordering\Application\UseCases\DeliveryZones\RestoreDeliveryZone;
use App\Modules\Ordering\Application\UseCases\DeliveryZones\ToggleDeliveryZone;
use App\Modules\Ordering\Application\UseCases\DeliveryZones\UpdateDeliveryZone;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use Tests\Unit\Identity\PassThroughTransactionManager;
use Tests\Unit\Ordering\Fakes\FixedBranches;
use Tests\Unit\Ordering\Fakes\InMemoryDeliveryZoneRepository;

/**
 * The form as the admin sends it.
 *
 * @param  array<string, mixed>  $changes
 */
function zoneForm(array $changes = []): SaveDeliveryZoneInput
{
    return new SaveDeliveryZoneInput(...[
        'id' => null, 'name' => 'Gulshan', 'locationId' => 1, 'feeTaka' => '80', 'minimumOrderTaka' => '', 'isActive' => true, ...$changes,
    ]);
}

beforeEach(function (): void {
    $this->zones = new InMemoryDeliveryZoneRepository;
    $reader = new DeliveryZoneInputReader(new FixedBranches(new Branch(1, 'Gulshan branch', true), new Branch(2, 'Dhanmondi branch', false)));
    $this->create = fn (array $changes = []): int => (new CreateDeliveryZone($this->zones, $reader))->handle(zoneForm($changes));
    $this->update = fn (int $id, array $changes = []) => (new UpdateDeliveryZone($this->zones, $reader))->handle(zoneForm(['id' => $id, ...$changes]));
});

it('creates zones at the end of the list', function (): void {
    ($this->create)();
    $id = ($this->create)(['name' => 'Banani', 'isActive' => false]);

    expect($this->zones->find($id)->details()->name)->toBe('Banani')
        ->and($this->zones->find($id)->isActive())->toBeFalse()
        ->and($this->zones->find($id)->sort()->value())->toBe(20);
});

it('accepts a branch that is switched off, but no unknown one', function (): void {
    ($this->create)(['locationId' => 2]);

    expect(fn () => ($this->create)(['locationId' => 99]))->toThrow(ValidationFailed::class)
        ->and($this->zones->all())->toHaveCount(1);
});

it('reports a wrong branch together with the other problems', function (): void {
    try {
        ($this->create)(['name' => '', 'locationId' => 99]);
    } catch (ValidationFailed $failure) {
        expect(array_keys($failure->errors()))->toBe(['name', 'location_id'])
            ->and($this->zones->all())->toBe([]);

        return;
    }

    throw new RuntimeException('A wrong form must be refused.');
});

it('changes everything but the position', function (): void {
    ($this->create)();
    $id = ($this->create)();

    ($this->update)($id, ['name' => 'Banani', 'locationId' => 2, 'feeTaka' => '60', 'minimumOrderTaka' => '500', 'isActive' => false]);

    $zone = $this->zones->find($id);

    expect($zone->details()->name)->toBe('Banani')
        ->and($zone->details()->locationId)->toBe(2)
        ->and($zone->details()->fee->poisha())->toBe(6000)
        ->and($zone->details()->minimumOrder->poisha())->toBe(50000)
        ->and($zone->isActive())->toBeFalse()
        ->and($zone->sort()->value())->toBe(20);
    expect(fn () => ($this->update)(99))->toThrow(EntityNotFound::class);
});

it('keeps the zone as it was when a change is wrong', function (): void {
    $id = ($this->create)();

    expect(fn () => ($this->update)($id, ['name' => 'Banani', 'feeTaka' => 'free']))->toThrow(ValidationFailed::class)
        ->and($this->zones->find($id)->details()->name)->toBe('Gulshan');
});

it('switches, deletes and restores a zone', function (): void {
    $input = new DeliveryZoneIdInput(($this->create)());

    expect((new ToggleDeliveryZone($this->zones))->handle($input))->toBeFalse()
        ->and((new ToggleDeliveryZone($this->zones))->handle($input))->toBeTrue();

    (new DeleteDeliveryZone($this->zones))->handle($input);
    expect($this->zones->all())->toBe([]);
    expect(fn () => (new DeleteDeliveryZone($this->zones))->handle($input))->toThrow(EntityNotFound::class);
    expect(fn () => (new ToggleDeliveryZone($this->zones))->handle($input))->toThrow(EntityNotFound::class);

    (new RestoreDeliveryZone($this->zones))->handle($input);
    expect($this->zones->all())->toHaveCount(1);
});

it('deletes a zone of the Trash forever, and no other', function (): void {
    $input = new DeliveryZoneIdInput(($this->create)());
    $forceDelete = new ForceDeleteDeliveryZone($this->zones);

    expect(fn () => $forceDelete->handle($input))->toThrow(EntityNotFound::class);

    (new DeleteDeliveryZone($this->zones))->handle($input);
    $forceDelete->handle($input);

    expect(fn () => (new RestoreDeliveryZone($this->zones))->handle($input))->toThrow(EntityNotFound::class);
});

it('saves a new order and refuses a zone twice or an unknown one', function (): void {
    $first = ($this->create)();
    $second = ($this->create)();
    $reorder = fn (array $ids) => (new ReorderDeliveryZones($this->zones, new PassThroughTransactionManager))->handle(new ReorderDeliveryZonesInput($ids));

    $reorder([$second, $first]);

    expect($this->zones->all()[0]->id())->toBe($second);
    expect(fn () => $reorder([$first, $first]))->toThrow(ValidationFailed::class);
    expect(fn () => $reorder([99]))->toThrow(EntityNotFound::class);
});
