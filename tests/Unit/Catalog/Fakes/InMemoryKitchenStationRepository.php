<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Fakes;

use App\Modules\Catalog\Domain\Entities\KitchenStation;
use App\Modules\Catalog\Domain\Repositories\KitchenStationRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * A KitchenStationRepository that keeps everything in an array, for use case tests.
 * It stores a copy on save() and hands out a copy on find(), like a database would:
 * changing an entity without saving it changes nothing here.
 */
final class InMemoryKitchenStationRepository implements KitchenStationRepository
{
    /** @var array<int, KitchenStation> */
    private array $stations = [];

    /** @var array<int, true> */
    private array $deleted = [];

    /** @var array<int, true> */
    private array $usedByCategories = [];

    /** @var array<int, true> */
    private array $usedByTrashedCategories = [];

    private int $nextId = 1;

    public function find(int $id): ?KitchenStation
    {
        if (! isset($this->stations[$id]) || isset($this->deleted[$id])) {
            return null;
        }

        return clone $this->stations[$id];
    }

    public function save(KitchenStation $station): int
    {
        $id = $station->id() ?? $this->nextId++;
        $this->stations[$id] = KitchenStation::reconstitute($id, $station->name(), $station->sort(), $station->isActive());

        return $id;
    }

    public function delete(int $id): void
    {
        if ($this->find($id) === null) {
            throw EntityNotFound::withId('Kitchen station', $id);
        }

        $this->deleted[$id] = true;
    }

    public function restore(int $id): void
    {
        if (! isset($this->deleted[$id])) {
            throw EntityNotFound::withId('Kitchen station', $id);
        }

        unset($this->deleted[$id]);
    }

    public function findTrashed(int $id): ?KitchenStation
    {
        return isset($this->deleted[$id]) ? clone $this->stations[$id] : null;
    }

    public function isUsedIncludingTrash(int $id): bool
    {
        return isset($this->usedByCategories[$id]) || isset($this->usedByTrashedCategories[$id]);
    }

    public function forceDelete(int $id): void
    {
        if (! isset($this->deleted[$id])) {
            throw EntityNotFound::withId('Kitchen station', $id);
        }

        unset($this->stations[$id], $this->deleted[$id]);
    }

    public function isUsedByCategories(int $id): bool
    {
        return isset($this->usedByCategories[$id]);
    }

    public function nextSortOrder(): SortOrder
    {
        $highest = null;

        foreach ($this->stations as $id => $station) {
            if (! isset($this->deleted[$id]) && ($highest === null || $highest->isBefore($station->sort()))) {
                $highest = $station->sort();
            }
        }

        return $highest === null ? SortOrder::first() : $highest->next();
    }

    // Test helpers (not part of the interface)

    public function markUsedByCategories(int $id): void
    {
        $this->usedByCategories[$id] = true;
    }

    public function isDeleted(int $id): bool
    {
        return isset($this->deleted[$id]);
    }

    public function markUsedByTrashedCategories(int $id): void
    {
        $this->usedByTrashedCategories[$id] = true;
    }

    public function exists(int $id): bool
    {
        return isset($this->stations[$id]);
    }
}
