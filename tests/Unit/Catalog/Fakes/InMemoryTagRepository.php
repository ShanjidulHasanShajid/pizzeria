<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Fakes;

use App\Modules\Catalog\Domain\Entities\Tag;
use App\Modules\Catalog\Domain\Repositories\TagRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * A TagRepository in an array, for use case tests. Copies in, copies out.
 */
final class InMemoryTagRepository implements TagRepository
{
    /** @var array<int, Tag> */
    private array $tags = [];

    /** @var array<int, true> */
    private array $deleted = [];

    private int $nextId = 1;

    public function find(int $id): ?Tag
    {
        if (! isset($this->tags[$id]) || isset($this->deleted[$id])) {
            return null;
        }

        return clone $this->tags[$id];
    }

    public function save(Tag $tag): int
    {
        $id = $tag->id() ?? $this->nextId++;
        $this->tags[$id] = Tag::reconstitute($id, $tag->name(), $tag->slug(), $tag->type(), $tag->icon(), $tag->sort(), $tag->isActive());

        return $id;
    }

    public function delete(int $id): void
    {
        if ($this->find($id) === null) {
            throw EntityNotFound::withId('Tag', $id);
        }

        $this->deleted[$id] = true;
    }

    public function restore(int $id): void
    {
        if (! isset($this->deleted[$id])) {
            throw EntityNotFound::withId('Tag', $id);
        }

        unset($this->deleted[$id]);
    }

    public function findTrashed(int $id): ?Tag
    {
        return isset($this->deleted[$id]) ? clone $this->tags[$id] : null;
    }

    public function forceDelete(int $id): void
    {
        if (! isset($this->deleted[$id])) {
            throw EntityNotFound::withId('Tag', $id);
        }

        unset($this->tags[$id], $this->deleted[$id]);
    }

    /** Looks at deleted ones too, like the real repository. */
    public function slugExists(Slug $slug, ?int $ignoreId = null): bool
    {
        foreach ($this->tags as $id => $tag) {
            if ($id !== $ignoreId && $tag->slug()->value() === $slug->value()) {
                return true;
            }
        }

        return false;
    }

    public function nextSortOrder(): SortOrder
    {
        $highest = null;

        foreach ($this->tags as $id => $tag) {
            if (! isset($this->deleted[$id]) && ($highest === null || $highest->isBefore($tag->sort()))) {
                $highest = $tag->sort();
            }
        }

        return $highest === null ? SortOrder::first() : $highest->next();
    }

    // Test helpers (not part of the interface)

    public function isDeleted(int $id): bool
    {
        return isset($this->deleted[$id]);
    }

    public function exists(int $id): bool
    {
        return isset($this->tags[$id]);
    }
}
