<?php

declare(strict_types=1);

namespace Tests\Unit\Customization\Fakes;

use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use RuntimeException;

/**
 * An OptionGroupRepository in an array, for use case tests. Copies in, copies out,
 * and it gives new groups and new options an id, like the database does.
 */
final class InMemoryOptionGroupRepository implements OptionGroupRepository
{
    /** @var array<int, OptionGroup> */
    private array $groups = [];

    /** @var array<int, true> */
    private array $deleted = [];

    private int $nextGroupId = 1;

    private int $nextOptionId = 1;

    private bool $failNextSave = false;

    public function find(int $id): ?OptionGroup
    {
        if (! isset($this->groups[$id]) || isset($this->deleted[$id])) {
            return null;
        }

        return $this->copy($this->groups[$id], $id);
    }

    public function findByIds(array $ids): array
    {
        $found = [];

        foreach ($ids as $id) {
            $group = $this->find($id);

            if ($group !== null) {
                $found[] = $group;
            }
        }

        usort($found, fn (OptionGroup $a, OptionGroup $b): int => $a->sort()->value() <=> $b->sort()->value());

        return $found;
    }

    public function save(OptionGroup $group): int
    {
        if ($this->failNextSave) {
            $this->failNextSave = false;

            throw new RuntimeException('The database is down.');
        }

        $id = $group->id() ?? $this->nextGroupId;
        $this->nextGroupId = max($this->nextGroupId, $id + 1);
        $this->groups[$id] = $this->copy($group, $id);

        return $id;
    }

    public function delete(int $id): void
    {
        if ($this->find($id) === null) {
            throw EntityNotFound::withId('Option group', $id);
        }

        $this->deleted[$id] = true;
    }

    public function restore(int $id): void
    {
        if (! isset($this->deleted[$id])) {
            throw EntityNotFound::withId('Option group', $id);
        }

        unset($this->deleted[$id]);
    }

    public function nextSortOrder(): SortOrder
    {
        $highest = null;

        foreach ($this->groups as $id => $group) {
            if (! isset($this->deleted[$id]) && ($highest === null || $highest->isBefore($group->sort()))) {
                $highest = $group->sort();
            }
        }

        return $highest === null ? SortOrder::first() : $highest->next();
    }

    // Test helper (not part of the interface)

    public function failNextSave(): void
    {
        $this->failNextSave = true;
    }

    private function copy(OptionGroup $group, int $id): OptionGroup
    {
        return OptionGroup::reconstitute(
            $id,
            $group->name(),
            $group->displayTitle(),
            $group->description(),
            $group->isActive(),
            $group->sort(),
            array_map(fn (Option $option): Option => $this->copyOption($option), $group->options()),
        );
    }

    private function copyOption(Option $option): Option
    {
        $id = $option->id() ?? $this->nextOptionId;
        $this->nextOptionId = max($this->nextOptionId, $id + 1);

        return Option::reconstitute(
            $id,
            $option->name(),
            $option->imagePath(),
            $option->isVeg(),
            $option->isAvailable(),
            $option->sort(),
            $option->priceMode(),
            $option->flatPrice(),
            $option->sizePrices(),
        );
    }
}
