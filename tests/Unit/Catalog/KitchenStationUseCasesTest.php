<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Application\DTOs\CreateKitchenStationInput;
use App\Modules\Catalog\Application\DTOs\ReorderInput;
use App\Modules\Catalog\Application\DTOs\UpdateKitchenStationInput;
use App\Modules\Catalog\Application\UseCases\KitchenStations\CreateKitchenStation;
use App\Modules\Catalog\Application\UseCases\KitchenStations\DeleteKitchenStation;
use App\Modules\Catalog\Application\UseCases\KitchenStations\ForceDeleteKitchenStation;
use App\Modules\Catalog\Application\UseCases\KitchenStations\ReorderKitchenStations;
use App\Modules\Catalog\Application\UseCases\KitchenStations\RestoreKitchenStation;
use App\Modules\Catalog\Application\UseCases\KitchenStations\UpdateKitchenStation;
use App\Modules\Catalog\Domain\Exceptions\CatalogItemInUse;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use Tests\Unit\Catalog\Fakes\InMemoryKitchenStationRepository;
use Tests\Unit\Identity\PassThroughTransactionManager;

beforeEach(function (): void {
    $this->stations = new InMemoryKitchenStationRepository;
    $this->transactions = new PassThroughTransactionManager;
    $this->createStation = fn (string $name): int => (new CreateKitchenStation($this->stations))
        ->handle(new CreateKitchenStationInput($name));
});

it('creates active stations at the end of the list, in steps of 10', function (): void {
    $oven = ($this->createStation)('Oven');
    $fryer = ($this->createStation)('Fryer');

    expect($this->stations->find($oven)->sort()->value())->toBe(10)
        ->and($this->stations->find($fryer)->sort()->value())->toBe(20)
        ->and($this->stations->find($fryer)->isActive())->toBeTrue();
});

it('renames a station and switches it off', function (): void {
    $id = ($this->createStation)('Oven');

    (new UpdateKitchenStation($this->stations))->handle(new UpdateKitchenStationInput($id, ' Pizza Oven ', isActive: false));

    expect($this->stations->find($id)->name())->toBe('Pizza Oven')
        ->and($this->stations->find($id)->isActive())->toBeFalse();
});

it('reports an unknown station when updating', function (): void {
    (new UpdateKitchenStation($this->stations))->handle(new UpdateKitchenStationInput(99, 'Oven', isActive: true));
})->throws(EntityNotFound::class);

it('moves an unused station to the trash and restores it', function (): void {
    $id = ($this->createStation)('Grill');

    (new DeleteKitchenStation($this->stations, $this->transactions))->handle(new CatalogItemInput($id));
    expect($this->stations->isDeleted($id))->toBeTrue();

    (new RestoreKitchenStation($this->stations))->handle(new CatalogItemInput($id));
    expect($this->stations->find($id))->not->toBeNull();
});

it('refuses to delete a station that categories use', function (): void {
    $id = ($this->createStation)('Oven');
    $this->stations->markUsedByCategories($id);

    expect(fn () => (new DeleteKitchenStation($this->stations, $this->transactions))->handle(new CatalogItemInput($id)))
        ->toThrow(CatalogItemInUse::class, 'used by categories');
    expect($this->stations->isDeleted($id))->toBeFalse();
});

it('saves a new order in steps of 10', function (): void {
    $oven = ($this->createStation)('Oven');
    $fryer = ($this->createStation)('Fryer');
    $grill = ($this->createStation)('Grill');

    (new ReorderKitchenStations($this->stations, $this->transactions))->handle(new ReorderInput([$grill, $oven, $fryer]));

    expect($this->stations->find($grill)->sort()->value())->toBe(10)
        ->and($this->stations->find($oven)->sort()->value())->toBe(20)
        ->and($this->stations->find($fryer)->sort()->value())->toBe(30);
});

it('refuses a new order with a station twice or an unknown station', function (): void {
    $oven = ($this->createStation)('Oven');
    $reorder = new ReorderKitchenStations($this->stations, $this->transactions);

    expect(fn () => $reorder->handle(new ReorderInput([$oven, $oven])))->toThrow(ValidationFailed::class);
    expect(fn () => $reorder->handle(new ReorderInput([$oven, 99])))->toThrow(EntityNotFound::class);
});

it('deletes a station in the trash forever', function (): void {
    $id = ($this->createStation)('Old Grill');
    $this->stations->delete($id);

    (new ForceDeleteKitchenStation($this->stations))->handle(new CatalogItemInput($id));

    expect($this->stations->exists($id))->toBeFalse();
});

it('deletes a station forever only from the trash, and not while categories in the trash use it', function (): void {
    $forceDelete = new ForceDeleteKitchenStation($this->stations);
    $live = ($this->createStation)('Oven');
    $trashed = ($this->createStation)('Old Grill');
    $this->stations->markUsedByTrashedCategories($trashed);
    $this->stations->delete($trashed);

    expect(fn () => $forceDelete->handle(new CatalogItemInput($live)))->toThrow(EntityNotFound::class);
    expect(fn () => $forceDelete->handle(new CatalogItemInput($trashed)))->toThrow(CatalogItemInUse::class, 'deleted forever');
    expect($this->stations->exists($trashed))->toBeTrue();
});
