<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Fakes;

use App\Modules\Catalog\Domain\Entities\Category;
use App\Modules\Catalog\Domain\Repositories\CategoryRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use RuntimeException;

/**
 * A CategoryRepository in an array, for use case tests. Copies in, copies out.
 * "Has children" is worked out from the stored categories, like the real one does.
 * Products live in another table, so a test says which categories have them.
 */
final class InMemoryCategoryRepository implements CategoryRepository
{
    /** @var array<int, Category> */
    private array $categories = [];

    /** @var array<int, true> */
    private array $deleted = [];

    /** @var array<int, true> categories with products that are not deleted */
    private array $withProducts = [];

    /** @var array<int, true> categories with products in the Trash */
    private array $withTrashedProducts = [];

    private int $nextId = 1;

    /** When set, the next save() throws, to test what happens after a failed save. */
    private bool $failNextSave = false;

    public function find(int $id): ?Category
    {
        if (! isset($this->categories[$id]) || isset($this->deleted[$id])) {
            return null;
        }

        return clone $this->categories[$id];
    }

    public function findTrashed(int $id): ?Category
    {
        return isset($this->deleted[$id]) ? clone $this->categories[$id] : null;
    }

    public function save(Category $category): int
    {
        if ($this->failNextSave) {
            $this->failNextSave = false;

            throw new RuntimeException('The database is down.');
        }

        $id = $category->id() ?? $this->nextId++;
        $this->categories[$id] = Category::reconstitute(
            $id,
            $category->name(),
            $category->slug(),
            $category->description(),
            $category->image(),
            $category->parentId(),
            $category->kitchenStationId(),
            $category->sort(),
            $category->isActive(),
            $category->isFeatured(),
            $category->isShownInMegaMenu(),
        );

        return $id;
    }

    public function delete(int $id): void
    {
        if ($this->find($id) === null) {
            throw EntityNotFound::withId('Category', $id);
        }

        $this->deleted[$id] = true;
    }

    public function restore(int $id): void
    {
        if (! isset($this->deleted[$id])) {
            throw EntityNotFound::withId('Category', $id);
        }

        unset($this->deleted[$id]);
    }

    public function forceDelete(int $id): void
    {
        if (! isset($this->deleted[$id])) {
            throw EntityNotFound::withId('Category', $id);
        }

        unset($this->categories[$id], $this->deleted[$id]);
    }

    public function slugExists(Slug $slug, ?int $ignoreId = null): bool
    {
        foreach ($this->categories as $id => $category) {
            if ($id !== $ignoreId && $category->slug()->value() === $slug->value()) {
                return true;
            }
        }

        return false;
    }

    public function hasProducts(int $id): bool
    {
        return isset($this->withProducts[$id]);
    }

    public function hasChildren(int $id): bool
    {
        foreach ($this->categories as $childId => $category) {
            if ($category->parentId() === $id && ! isset($this->deleted[$childId])) {
                return true;
            }
        }

        return false;
    }

    public function isUsedIncludingTrash(int $id): bool
    {
        if (isset($this->withProducts[$id]) || isset($this->withTrashedProducts[$id])) {
            return true;
        }

        foreach ($this->categories as $category) {
            if ($category->parentId() === $id) {
                return true;
            }
        }

        return false;
    }

    public function nextSortOrder(?int $parentId): SortOrder
    {
        $highest = null;

        foreach ($this->categories as $id => $category) {
            if (isset($this->deleted[$id]) || $category->parentId() !== $parentId) {
                continue;
            }

            if ($highest === null || $highest->isBefore($category->sort())) {
                $highest = $category->sort();
            }
        }

        return $highest === null ? SortOrder::first() : $highest->next();
    }

    // Test helpers (not part of the interface)

    public function giveProducts(int $id): void
    {
        $this->withProducts[$id] = true;
    }

    public function giveTrashedProducts(int $id): void
    {
        $this->withTrashedProducts[$id] = true;
    }

    public function failNextSave(): void
    {
        $this->failNextSave = true;
    }

    public function isDeleted(int $id): bool
    {
        return isset($this->deleted[$id]);
    }

    public function exists(int $id): bool
    {
        return isset($this->categories[$id]);
    }
}
