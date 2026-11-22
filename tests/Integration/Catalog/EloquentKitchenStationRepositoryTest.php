<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entities\KitchenStation;
use App\Modules\Catalog\Domain\Repositories\KitchenStationRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\KitchenStation as KitchenStationModel;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function (): void {
    $this->repository = app(KitchenStationRepository::class);
});

it('saves a new station and finds it again', function (): void {
    $id = $this->repository->save(KitchenStation::create('Oven', SortOrder::fromInt(10)));

    $station = $this->repository->find($id);

    expect($station->id())->toBe($id)
        ->and($station->name())->toBe('Oven')
        ->and($station->sort()->value())->toBe(10)
        ->and($station->isActive())->toBeTrue();
});

it('updates a saved station instead of adding a second row', function (): void {
    $id = $this->repository->save(KitchenStation::create('Oven', SortOrder::fromInt(10)));
    $station = $this->repository->find($id);

    $station->rename('Pizza Oven');
    $station->deactivate();
    $savedId = $this->repository->save($station);

    expect($savedId)->toBe($id)
        ->and(KitchenStationModel::query()->count())->toBe(1)
        ->and($this->repository->find($id)->name())->toBe('Pizza Oven')
        ->and($this->repository->find($id)->isActive())->toBeFalse();
});

it('moves a station to the Trash and back', function (): void {
    $id = KitchenStationModel::factory()->create()->id;

    $this->repository->delete($id);

    expect($this->repository->find($id))->toBeNull();
    $this->assertSoftDeleted('kitchen_stations', ['id' => $id]);

    $this->repository->restore($id);

    expect($this->repository->find($id))->not->toBeNull();
});

it('knows when a category that is not deleted uses a station', function (): void {
    $oven = KitchenStationModel::factory()->create();
    $grill = KitchenStationModel::factory()->create();
    $pizza = CategoryModel::factory()->for($oven)->create();

    expect($this->repository->isUsedByCategories($oven->id))->toBeTrue()
        ->and($this->repository->isUsedByCategories($grill->id))->toBeFalse();

    $pizza->delete();

    expect($this->repository->isUsedByCategories($oven->id))->toBeFalse();
});

it('returns null for an unknown id and refuses to delete it', function (): void {
    expect($this->repository->find(424242))->toBeNull();
    expect(fn () => $this->repository->delete(424242))->toThrow(ModelNotFoundException::class);
});

it('gives the position after the last station that is not deleted', function (): void {
    expect($this->repository->nextSortOrder()->value())->toBe(10);

    KitchenStationModel::factory()->create(['sort' => 20]);
    KitchenStationModel::factory()->create(['sort' => 70])->delete();

    expect($this->repository->nextSortOrder()->value())->toBe(30);
});

it('finds a station in the Trash, sees categories in the Trash, and deletes it for good', function (): void {
    $station = KitchenStationModel::factory()->create(['name' => 'Old Grill']);
    $category = CategoryModel::factory()->for($station)->create();
    $category->delete();
    $station->delete();

    expect($this->repository->findTrashed($station->id)?->name())->toBe('Old Grill')
        ->and($this->repository->isUsedByCategories($station->id))->toBeFalse()
        ->and($this->repository->isUsedIncludingTrash($station->id))->toBeTrue();

    $category->forceDelete();
    $this->repository->forceDelete($station->id);

    $this->assertDatabaseMissing('kitchen_stations', ['id' => $station->id]);
});
