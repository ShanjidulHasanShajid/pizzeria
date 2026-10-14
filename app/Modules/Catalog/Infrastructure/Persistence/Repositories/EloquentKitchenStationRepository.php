<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Repositories;

use App\Modules\Catalog\Domain\Entities\KitchenStation;
use App\Modules\Catalog\Domain\Repositories\KitchenStationRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\KitchenStation as KitchenStationModel;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

final class EloquentKitchenStationRepository implements KitchenStationRepository
{
    public function find(int $id): ?KitchenStation
    {
        $model = KitchenStationModel::query()->find($id);

        return $model === null ? null : $this->toEntity($model);
    }

    public function save(KitchenStation $station): int
    {
        $id = $station->id();
        $model = $id === null ? new KitchenStationModel : KitchenStationModel::query()->findOrFail($id);

        $model->fill([
            'name' => $station->name(),
            'sort' => $station->sort()->value(),
            'is_active' => $station->isActive(),
        ]);
        $model->save();

        return $model->id;
    }

    public function delete(int $id): void
    {
        KitchenStationModel::query()->findOrFail($id)->delete();
    }

    public function restore(int $id): void
    {
        KitchenStationModel::onlyTrashed()->findOrFail($id)->restore();
    }

    public function isUsedByCategories(int $id): bool
    {
        // Deleted categories are left out automatically (SoftDeletes).
        return CategoryModel::query()->where('kitchen_station_id', $id)->exists();
    }

    private function toEntity(KitchenStationModel $model): KitchenStation
    {
        return KitchenStation::reconstitute($model->id, $model->name, SortOrder::fromInt($model->sort), $model->is_active);
    }
}
