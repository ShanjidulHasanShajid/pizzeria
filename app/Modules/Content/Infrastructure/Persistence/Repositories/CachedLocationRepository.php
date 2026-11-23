<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Repositories;

use App\Modules\Content\Domain\Repositories\LocationRepository;
use App\Modules\Content\Domain\ValueObjects\Coordinates;
use App\Modules\Content\Domain\ValueObjects\Location;
use App\Modules\Content\Domain\ValueObjects\LocationDetails;
use App\Modules\Content\Domain\ValueObjects\OpeningHours;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;

/**
 * Caches the list of branches. "Open now" is worked out later, with the clock.
 */
final class CachedLocationRepository implements LocationRepository
{
    public const CACHE_KEY = 'content.locations';

    public function __construct(
        private readonly LocationRepository $inner,
        private readonly Cache $cache,
    ) {}

    public function all(): array
    {
        /** @var list<array{id: int, name: string, address: string, area: string, phone: string, map: string, point: array{string, string}|null, pickup: bool, hours: array<string, array{string, string}|null>, active: bool, sort: int}> $rows */
        $rows = $this->cache->rememberForever(self::CACHE_KEY, fn (): array => array_map(
            fn (Location $location): array => [
                'id' => (int) $location->id,
                'name' => $location->details->name,
                'address' => $location->details->address,
                'area' => $location->details->area,
                'phone' => $location->details->phone,
                'map' => $location->details->mapEmbedUrl,
                'point' => $location->details->coordinates === null ? null : [$location->details->coordinates->latitude, $location->details->coordinates->longitude],
                'pickup' => $location->details->isPickupAvailable,
                'hours' => $location->hours->toWeek(),
                'active' => $location->isActive,
                'sort' => $location->sort->value(),
            ],
            $this->inner->all(),
        ));

        return array_map(fn (array $row): Location => Location::reconstitute(
            $row['id'],
            LocationDetails::reconstitute(
                $row['name'],
                $row['address'],
                $row['area'],
                $row['phone'],
                $row['map'],
                $row['point'] === null ? null : Coordinates::fromStrings($row['point'][0], $row['point'][1]),
                $row['pickup'],
            ),
            OpeningHours::fromWeek($row['hours']),
            $row['active'],
            SortOrder::fromInt($row['sort']),
        ), $rows);
    }

    public function find(int $id): ?Location
    {
        return $this->inner->find($id);
    }

    public function save(Location $location): int
    {
        $id = $this->inner->save($location);
        $this->clear();

        return $id;
    }

    public function delete(int $id): void
    {
        $this->inner->delete($id);
        $this->clear();
    }

    public function restore(int $id): void
    {
        $this->inner->restore($id);
        $this->clear();
    }

    public function forceDelete(int $id): void
    {
        $this->inner->forceDelete($id); // was not in the cached list
    }

    public function nextSortOrder(): SortOrder
    {
        return $this->inner->nextSortOrder();
    }

    private function clear(): void
    {
        DB::afterCommit(fn () => $this->cache->forget(self::CACHE_KEY));
    }
}
