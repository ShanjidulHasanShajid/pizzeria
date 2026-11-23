<?php

declare(strict_types=1);

namespace Tests\Unit\Content\Fakes;

use App\Modules\Content\Domain\Repositories\LocationRepository;
use App\Modules\Content\Domain\ValueObjects\Location;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * A LocationRepository in an array, for use case tests.
 */
final class InMemoryLocationRepository implements LocationRepository
{
    /** @var array<int, Location> */
    private array $locations = [];

    /** @var array<int, true> */
    private array $deleted = [];

    private int $nextId = 1;

    public function all(): array
    {
        $locations = array_filter($this->locations, fn (Location $location): bool => ! isset($this->deleted[(int) $location->id]));
        usort($locations, fn (Location $a, Location $b): int => $a->sort->value() <=> $b->sort->value());

        return $locations;
    }

    public function find(int $id): ?Location
    {
        return isset($this->deleted[$id]) ? null : ($this->locations[$id] ?? null);
    }

    public function save(Location $location): int
    {
        $id = $location->id ?? $this->nextId++;
        $this->locations[$id] = Location::reconstitute($id, $location->details, $location->hours, $location->isActive, $location->sort);

        return $id;
    }

    public function delete(int $id): void
    {
        $this->deleted[$id] = true;
    }

    public function restore(int $id): void
    {
        if (! isset($this->deleted[$id])) {
            throw EntityNotFound::withId('Location', $id);
        }

        unset($this->deleted[$id]);
    }

    public function forceDelete(int $id): void
    {
        if (! isset($this->deleted[$id])) {
            throw EntityNotFound::withId('Location', $id);
        }

        unset($this->locations[$id], $this->deleted[$id]);
    }

    public function nextSortOrder(): SortOrder
    {
        $locations = $this->all();

        return $locations === [] ? SortOrder::first() : $locations[count($locations) - 1]->sort->next();
    }
}
