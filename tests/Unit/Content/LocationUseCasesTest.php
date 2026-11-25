<?php

declare(strict_types=1);

use App\Modules\Content\Application\DTOs\LocationIdInput;
use App\Modules\Content\Application\DTOs\ReorderLocationsInput;
use App\Modules\Content\Application\DTOs\SaveLocationInput;
use App\Modules\Content\Application\Ports\LocationUsage;
use App\Modules\Content\Application\Services\LocationInputReader;
use App\Modules\Content\Application\UseCases\Locations\CreateLocation;
use App\Modules\Content\Application\UseCases\Locations\DeleteLocation;
use App\Modules\Content\Application\UseCases\Locations\ForceDeleteLocation;
use App\Modules\Content\Application\UseCases\Locations\ReorderLocations;
use App\Modules\Content\Application\UseCases\Locations\RestoreLocation;
use App\Modules\Content\Application\UseCases\Locations\ToggleLocation;
use App\Modules\Content\Application\UseCases\Locations\UpdateLocation;
use App\Modules\Content\Domain\Exceptions\LocationInUse;
use App\Modules\Content\Infrastructure\Queries\RepositoryBranchScheduleQuery;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use Tests\Unit\Content\Fakes\InMemoryLocationRepository;
use Tests\Unit\Identity\PassThroughTransactionManager;

/**
 * The location form as the admin sends it: open on Mondays.
 *
 * @param  array<string, mixed>  $changes
 */
function locationForm(array $changes = []): SaveLocationInput
{
    return new SaveLocationInput(...[
        'id' => null, 'name' => 'Dhanmondi', 'address' => 'Road 27', 'area' => '', 'phone' => '01712 345678',
        'mapEmbed' => '', 'latitude' => '', 'longitude' => '', 'hours' => ['monday' => ['11:00', '23:00']],
        'isPickupAvailable' => true, 'isActive' => true, ...$changes,
    ]);
}

/** Says that every branch has (or has no) delivery zones. */
function locationUsage(bool $hasZones): LocationUsage
{
    return new class($hasZones) implements LocationUsage
    {
        public function __construct(private readonly bool $hasZones) {}

        public function hasDeliveryZones(int $locationId): bool
        {
            return $this->hasZones;
        }
    };
}

beforeEach(function (): void {
    $this->locations = new InMemoryLocationRepository;
    $this->create = fn (array $changes = []): int => (new CreateLocation($this->locations, new LocationInputReader))->handle(locationForm($changes));
    $this->update = fn (int $id, array $changes = []) => (new UpdateLocation($this->locations, new LocationInputReader))->handle(locationForm(['id' => $id, ...$changes]));
});

it('creates locations at the end of the list', function (): void {
    ($this->create)();
    $id = ($this->create)(['name' => 'Gulshan']);

    expect($this->locations->find($id)->details->name)->toBe('Gulshan')
        ->and($this->locations->find($id)->sort->value())->toBe(20)
        ->and($this->locations->find($id)->hours->toWeek()['monday'])->toBe(['11:00', '23:00']);
});

it('reports wrong details and wrong hours together and saves nothing', function (): void {
    try {
        ($this->create)(['name' => '', 'hours' => ['monday' => ['11:00', '']]]);
    } catch (ValidationFailed $failure) {
        expect(array_keys($failure->errors()))->toBe(['name', 'hours.monday'])
            ->and($this->locations->all())->toBe([]);

        return;
    }

    throw new RuntimeException('A wrong form must be refused.');
});

it('changes everything but the position', function (): void {
    ($this->create)();
    $id = ($this->create)();

    ($this->update)($id, ['name' => 'Gulshan', 'hours' => [], 'isPickupAvailable' => false, 'isActive' => false]);

    $location = $this->locations->find($id);

    expect($location->details->name)->toBe('Gulshan')
        ->and($location->details->isPickupAvailable)->toBeFalse()
        ->and($location->hours->toWeek()['monday'])->toBeNull()
        ->and($location->isActive)->toBeFalse()
        ->and($location->sort->value())->toBe(20);
    expect(fn () => ($this->update)(99))->toThrow(EntityNotFound::class);
});

it('switches, deletes and restores a location', function (): void {
    $input = new LocationIdInput(($this->create)());

    expect((new ToggleLocation($this->locations))->handle($input))->toBeFalse()
        ->and((new ToggleLocation($this->locations))->handle($input))->toBeTrue();

    (new DeleteLocation($this->locations))->handle($input);
    expect($this->locations->all())->toBe([]);
    expect(fn () => (new DeleteLocation($this->locations))->handle($input))->toThrow(EntityNotFound::class);
    expect(fn () => (new ToggleLocation($this->locations))->handle($input))->toThrow(EntityNotFound::class);

    (new RestoreLocation($this->locations))->handle($input);
    expect($this->locations->all())->toHaveCount(1);
});

it('saves a new order and refuses a location twice or an unknown one', function (): void {
    $first = ($this->create)();
    $second = ($this->create)();
    $reorder = fn (array $ids) => (new ReorderLocations($this->locations, new PassThroughTransactionManager))->handle(new ReorderLocationsInput($ids));

    $reorder([$second, $first]);

    expect($this->locations->all()[0]->id)->toBe($second);
    expect(fn () => $reorder([$first, $first]))->toThrow(ValidationFailed::class);
    expect(fn () => $reorder([99]))->toThrow(EntityNotFound::class);
});

it('tells other modules when a branch is open; unknown and switched-off ones never are', function (): void {
    $id = ($this->create)();
    $schedule = new RepositoryBranchScheduleQuery($this->locations);
    $monday = new DateTimeImmutable('2026-11-23 12:00');

    expect($schedule->isOpenAt($id, $monday))->toBeTrue()
        ->and($schedule->nextOpeningAfter($id, $monday)?->format('Y-m-d H:i'))->toBe('2026-11-30 11:00')
        ->and($schedule->isOpenAt(99, $monday))->toBeFalse()
        ->and($schedule->nextOpeningAfter(99, $monday))->toBeNull();

    (new ToggleLocation($this->locations))->handle(new LocationIdInput($id));

    expect($schedule->isOpenAt($id, $monday))->toBeFalse()
        ->and($schedule->nextOpeningAfter($id, $monday))->toBeNull();
});

it('deletes a location of the Trash forever, and no other', function (): void {
    $input = new LocationIdInput(($this->create)());
    $forceDelete = new ForceDeleteLocation($this->locations, locationUsage(false));

    expect(fn () => $forceDelete->handle($input))->toThrow(EntityNotFound::class);

    (new DeleteLocation($this->locations))->handle($input);
    $forceDelete->handle($input);

    expect(fn () => (new RestoreLocation($this->locations))->handle($input))->toThrow(EntityNotFound::class);
});

it('does not delete a location forever while delivery zones belong to it', function (): void {
    $input = new LocationIdInput(($this->create)());
    (new DeleteLocation($this->locations))->handle($input);

    expect(fn () => (new ForceDeleteLocation($this->locations, locationUsage(true)))->handle($input))->toThrow(LocationInUse::class);

    (new RestoreLocation($this->locations))->handle($input);
    expect($this->locations->all())->toHaveCount(1);
});
