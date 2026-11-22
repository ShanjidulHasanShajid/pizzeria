<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Fakes;

use App\Modules\Catalog\Domain\Entities\SizeTier;
use App\Modules\Catalog\Domain\Repositories\SizeTierRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * A SizeTierRepository in an array, for use case tests. Copies in, copies out.
 */
final class InMemorySizeTierRepository implements SizeTierRepository
{
    /** @var array<int, SizeTier> */
    private array $sizeTiers = [];

    /** @var array<int, true> */
    private array $deleted = [];

    /** @var array<int, true> */
    private array $inUse = [];

    /** @var array<int, true> */
    private array $usedInTrash = [];

    private int $nextId = 1;

    public function find(int $id): ?SizeTier
    {
        if (! isset($this->sizeTiers[$id]) || isset($this->deleted[$id])) {
            return null;
        }

        return clone $this->sizeTiers[$id];
    }

    public function save(SizeTier $sizeTier): int
    {
        $id = $sizeTier->id() ?? $this->nextId++;
        $this->sizeTiers[$id] = SizeTier::reconstitute($id, $sizeTier->name(), $sizeTier->slug(), $sizeTier->sort(), $sizeTier->isActive());

        return $id;
    }

    public function delete(int $id): void
    {
        if ($this->find($id) === null) {
            throw EntityNotFound::withId('Size tier', $id);
        }

        $this->deleted[$id] = true;
    }

    public function restore(int $id): void
    {
        if (! isset($this->deleted[$id])) {
            throw EntityNotFound::withId('Size tier', $id);
        }

        unset($this->deleted[$id]);
    }

    public function findTrashed(int $id): ?SizeTier
    {
        return isset($this->deleted[$id]) ? clone $this->sizeTiers[$id] : null;
    }

    public function isUsedIncludingTrash(int $id): bool
    {
        return isset($this->inUse[$id]) || isset($this->usedInTrash[$id]);
    }

    public function forceDelete(int $id): void
    {
        if (! isset($this->deleted[$id])) {
            throw EntityNotFound::withId('Size tier', $id);
        }

        unset($this->sizeTiers[$id], $this->deleted[$id]);
    }

    /** Looks at deleted ones too, like the real repository. */
    public function slugExists(Slug $slug, ?int $ignoreId = null): bool
    {
        foreach ($this->sizeTiers as $id => $sizeTier) {
            if ($id !== $ignoreId && $sizeTier->slug()->value() === $slug->value()) {
                return true;
            }
        }

        return false;
    }

    public function isInUse(int $id): bool
    {
        return isset($this->inUse[$id]);
    }

    public function nextSortOrder(): SortOrder
    {
        $highest = null;

        foreach ($this->sizeTiers as $id => $sizeTier) {
            if (! isset($this->deleted[$id]) && ($highest === null || $highest->isBefore($sizeTier->sort()))) {
                $highest = $sizeTier->sort();
            }
        }

        return $highest === null ? SortOrder::first() : $highest->next();
    }

    // Test helpers (not part of the interface)

    public function markInUse(int $id): void
    {
        $this->inUse[$id] = true;
    }

    public function isDeleted(int $id): bool
    {
        return isset($this->deleted[$id]);
    }

    /** A removed variant, or a variant of a product in the Trash, still uses this size. */
    public function markUsedInTrash(int $id): void
    {
        $this->usedInTrash[$id] = true;
    }

    public function exists(int $id): bool
    {
        return isset($this->sizeTiers[$id]);
    }
}
