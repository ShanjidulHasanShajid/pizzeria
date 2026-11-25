<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure\Persistence\Repositories;

use App\Modules\Ordering\Domain\Entities\DeliveryZone;
use App\Modules\Ordering\Domain\Repositories\DeliveryZoneRepository;
use App\Modules\Ordering\Infrastructure\Persistence\Mappers\DeliveryZoneMapper;
use App\Modules\Ordering\Infrastructure\Persistence\Models\DeliveryZone as DeliveryZoneModel;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

final class EloquentDeliveryZoneRepository implements DeliveryZoneRepository
{
    public function __construct(private readonly DeliveryZoneMapper $mapper) {}

    public function find(int $id): ?DeliveryZone
    {
        $model = DeliveryZoneModel::query()->find($id);

        return $model === null ? null : $this->mapper->toEntity($model);
    }

    public function save(DeliveryZone $zone): int
    {
        $id = $zone->id();
        $model = $id === null ? new DeliveryZoneModel : DeliveryZoneModel::query()->findOrFail($id);

        $model->fill($this->mapper->toRow($zone));
        $model->save();

        return $model->id;
    }

    public function delete(int $id): void
    {
        DeliveryZoneModel::query()->findOrFail($id)->delete();
    }

    public function restore(int $id): void
    {
        DeliveryZoneModel::onlyTrashed()->findOrFail($id)->restore();
    }

    public function forceDelete(int $id): void
    {
        DeliveryZoneModel::onlyTrashed()->findOrFail($id)->forceDelete();
    }

    public function nextSortOrder(): SortOrder
    {
        // max() skips rows in the Trash (SoftDeletes) and gives null for an empty table.
        $highest = DeliveryZoneModel::query()->max('sort');

        return $highest === null ? SortOrder::first() : SortOrder::fromInt((int) $highest)->next();
    }
}
