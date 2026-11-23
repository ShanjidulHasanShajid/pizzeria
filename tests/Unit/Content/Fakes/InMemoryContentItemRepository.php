<?php

declare(strict_types=1);

namespace Tests\Unit\Content\Fakes;

use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;
use App\Modules\Content\Domain\ValueObjects\ContentItem;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use RuntimeException;

/**
 * A ContentItemRepository in an array, for use case tests.
 */
final class InMemoryContentItemRepository implements ContentItemRepository
{
    /** @var array<string, array<int, ContentItem>> */
    private array $items = [];

    /** @var array<string, array<int, true>> */
    private array $deleted = [];

    private int $nextId = 1;

    private bool $failNextSave = false;

    public function all(ContentType $type): array
    {
        $items = array_filter(
            $this->items[$type->value] ?? [],
            fn (ContentItem $item): bool => ! isset($this->deleted[$type->value][(int) $item->id]),
        );
        usort($items, fn (ContentItem $a, ContentItem $b): int => $a->sort->value() <=> $b->sort->value());

        return $items;
    }

    public function find(ContentType $type, int $id): ?ContentItem
    {
        return isset($this->deleted[$type->value][$id]) ? null : ($this->items[$type->value][$id] ?? null);
    }

    public function save(ContentItem $item): int
    {
        if ($this->failNextSave) {
            $this->failNextSave = false;

            throw new RuntimeException('The database is down.');
        }

        $id = $item->id ?? $this->nextId++;
        $this->items[$item->type->value][$id] = ContentItem::reconstitute($id, $item->type, $item->values, $item->schedule, $item->isActive, $item->sort);

        return $id;
    }

    public function delete(ContentType $type, int $id): void
    {
        $this->deleted[$type->value][$id] = true;
    }

    public function restore(ContentType $type, int $id): void
    {
        if (! isset($this->deleted[$type->value][$id])) {
            throw EntityNotFound::withId($type->singular(), $id);
        }

        unset($this->deleted[$type->value][$id]);
    }

    public function findDeleted(ContentType $type, int $id): ?ContentItem
    {
        return isset($this->deleted[$type->value][$id]) ? ($this->items[$type->value][$id] ?? null) : null;
    }

    public function forceDelete(ContentType $type, int $id): void
    {
        unset($this->items[$type->value][$id], $this->deleted[$type->value][$id]);
    }

    public function nextSortOrder(ContentType $type): SortOrder
    {
        $items = $this->all($type);

        return $items === [] ? SortOrder::first() : $items[count($items) - 1]->sort->next();
    }

    // Test helper (not part of the interface)

    public function failNextSave(): void
    {
        $this->failNextSave = true;
    }
}
