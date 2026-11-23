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

    /** @var array<int, array{0: int, 1: Option}> option id => [group id, option] */
    private array $trashedOptions = [];

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

        // Like the database: options that are no longer in the group go to the Trash.
        foreach (isset($this->groups[$id]) ? $this->groups[$id]->options() : [] as $old) {
            if ($group->option((int) $old->id()) === null) {
                $this->trashedOptions[(int) $old->id()] = [$id, $old];
            }
        }

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

    public function findTrashed(int $id): ?OptionGroup
    {
        return isset($this->groups[$id], $this->deleted[$id]) ? $this->copy($this->groups[$id], $id) : null;
    }

    public function forceDelete(int $id): void
    {
        if ($this->findTrashed($id) === null) {
            throw EntityNotFound::withId('Option group', $id);
        }

        unset($this->groups[$id], $this->deleted[$id]);
        $this->trashedOptions = array_filter($this->trashedOptions, fn (array $trashed): bool => $trashed[0] !== $id);
    }

    public function imagePaths(int $id): array
    {
        $options = isset($this->groups[$id]) ? $this->groups[$id]->options() : [];

        foreach ($this->trashedOptions as [$groupId, $option]) {
            if ($groupId === $id) {
                $options[] = $option;
            }
        }

        return array_values(array_filter(array_map(fn (Option $option): ?string => $option->imagePath(), $options)));
    }

    public function findTrashedOption(int $optionId): ?Option
    {
        return isset($this->trashedOptions[$optionId]) ? $this->copyOption($this->trashedOptions[$optionId][1]) : null;
    }

    public function groupIdOfTrashedOption(int $optionId): ?int
    {
        return $this->trashedOptions[$optionId][0] ?? null;
    }

    public function restoreOption(int $optionId): void
    {
        [$groupId, $option] = $this->trashedOptions[$optionId] ?? throw EntityNotFound::withId('Option', $optionId);

        $this->groups[$groupId]->addOption($option);
        unset($this->trashedOptions[$optionId]);
    }

    public function forceDeleteOption(int $optionId): void
    {
        if (! isset($this->trashedOptions[$optionId])) {
            throw EntityNotFound::withId('Option', $optionId);
        }

        unset($this->trashedOptions[$optionId]);
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
