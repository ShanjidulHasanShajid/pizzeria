<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Repositories;

use App\Modules\Content\Domain\Repositories\LocationRepository;
use App\Modules\Content\Domain\ValueObjects\Coordinates;
use App\Modules\Content\Domain\ValueObjects\Location;
use App\Modules\Content\Domain\ValueObjects\LocationDetails;
use App\Modules\Content\Domain\ValueObjects\OpeningHours;
use App\Modules\Content\Infrastructure\Persistence\Models\Location as LocationModel;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

final class EloquentLocationRepository implements LocationRepository
{
    public function all(): array
    {
        $locations = [];

        foreach (LocationModel::query()->orderBy('sort')->orderBy('id')->get() as $model) {
            $locations[] = $this->toLocation($model);
        }

        return $locations;
    }

    public function find(int $id): ?Location
    {
        $model = LocationModel::query()->find($id);

        return $model === null ? null : $this->toLocation($model);
    }

    public function save(Location $location): int
    {
        $model = $location->id === null ? new LocationModel : LocationModel::query()->findOrFail($location->id);
        $details = $location->details;

        $model->fill([
            'name' => $details->name,
            'address' => $details->address,
            'area' => $details->area === '' ? null : $details->area,
            'phone' => $details->phone,
            'map_embed_url' => $details->mapEmbedUrl === '' ? null : $details->mapEmbedUrl,
            'latitude' => $details->coordinates?->latitude,
            'longitude' => $details->coordinates?->longitude,
            'opening_hours' => $location->hours->toWeek(),
            'is_pickup_available' => $details->isPickupAvailable,
            'sort' => $location->sort->value(),
            'is_active' => $location->isActive,
        ]);
        $model->save();

        return $model->id;
    }

    public function delete(int $id): void
    {
        LocationModel::query()->findOrFail($id)->delete();
    }

    public function restore(int $id): void
    {
        LocationModel::onlyTrashed()->findOrFail($id)->restore();
    }

    public function forceDelete(int $id): void
    {
        LocationModel::onlyTrashed()->findOrFail($id)->forceDelete();
    }

    public function nextSortOrder(): SortOrder
    {
        $highest = LocationModel::query()->max('sort');

        return $highest === null ? SortOrder::first() : SortOrder::fromInt((int) $highest)->next();
    }

    private function toLocation(LocationModel $model): Location
    {
        return Location::reconstitute(
            $model->id,
            LocationDetails::reconstitute(
                $model->name,
                $model->address,
                $model->area ?? '',
                $model->phone,
                $model->map_embed_url ?? '',
                $model->latitude === null || $model->longitude === null ? null : Coordinates::fromStrings($model->latitude, $model->longitude),
                $model->is_pickup_available,
            ),
            OpeningHours::fromWeek($model->opening_hours),
            $model->is_active,
            SortOrder::fromInt($model->sort),
        );
    }
}
