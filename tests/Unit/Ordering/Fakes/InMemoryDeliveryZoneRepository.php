<?php

declare(strict_types=1);

namespace Tests\Unit\Ordering\Fakes;

use App\Modules\Ordering\Domain\Entities\DeliveryZone;
use App\Modules\Ordering\Domain\Repositories\DeliveryZoneRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * A DeliveryZoneRepository in an array, for use case tests.
 */
final class InMemoryDeliveryZoneRepository implements DeliveryZoneRepository
{
    /** @var array<int, DeliveryZone> */
    private array $zones = [];

    /** @var array<int, true> */
    private array $deleted = [];

    private int $nextId = 1;

    /**
     * @return list<DeliveryZone> not deleted, in order
     */
    public function all(): array
    {
        $zones = array_filter($this->zones, fn (DeliveryZone $zone): bool => ! isset($this->deleted[(int) $zone->id()]));
        usort($zones, fn (DeliveryZone $a, DeliveryZone $b): int => $a->sort()->value() <=> $b->sort()->value());

        return $zones;
    }

    public function find(int $id): ?DeliveryZone
    {
        // A copy, as a database would give: changes count only after save().
        return isset($this->deleted[$id]) || ! isset($this->zones[$id]) ? null : clone $this->zones[$id];
    }

    public function save(DeliveryZone $zone): int
    {
        $id = $zone->id() ?? $this->nextId++;
        $this->zones[$id] = DeliveryZone::reconstitute($id, $zone->details(), $zone->isActive(), $zone->sort());

        return $id;
    }

    public function delete(int $id): void
    {
        $this->deleted[$id] = true;
    }

    public function restore(int $id): void
    {
        $this->mustBeDeleted($id);

        unset($this->deleted[$id]);
    }

    public function forceDelete(int $id): void
    {
        $this->mustBeDeleted($id);

        unset($this->zones[$id], $this->deleted[$id]);
    }

    public function nextSortOrder(): SortOrder
    {
        $zones = $this->all();

        return $zones === [] ? SortOrder::first() : $zones[count($zones) - 1]->sort()->next();
    }

    private function mustBeDeleted(int $id): void
    {
        if (! isset($this->deleted[$id])) {
            throw EntityNotFound::withId('Delivery zone', $id);
        }
    }
}
