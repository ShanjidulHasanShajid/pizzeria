<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\Repositories;

use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * The saved option groups. A group is always loaded and saved as a whole: with its options and their prices.
 */
interface OptionGroupRepository
{
    /** A group that is not deleted, or null. */
    public function find(int $id): ?OptionGroup;

    /**
     * The groups with these ids that are not deleted, in sort order. Unknown ids are left out.
     *
     * @param  list<int>  $ids
     * @return list<OptionGroup>
     */
    public function findByIds(array $ids): array;

    /**
     * Inserts the group when it has no id yet, otherwise updates it. Returns the id.
     * Options that are no longer in the group go to the Trash.
     */
    public function save(OptionGroup $group): int;

    /** Soft delete: the group goes to the Trash with everything in it. */
    public function delete(int $id): void;

    public function restore(int $id): void;

    /** The position after the last group that is not deleted: 10 for the first one, then +10. */
    public function nextSortOrder(): SortOrder;

    /** A group that is in the Trash, or null. */
    public function findTrashed(int $id): ?OptionGroup;

    /** Deletes a group that is in the Trash for good, with all its options and prices. */
    public function forceDelete(int $id): void;

    /**
     * The image paths of all options of a group, also of options in the Trash.
     *
     * @return list<string>
     */
    public function imagePaths(int $id): array;

    /** A single option that is in the Trash, or null. */
    public function findTrashedOption(int $optionId): ?Option;

    /** The group of an option that is in the Trash, or null. The group may be in the Trash too. */
    public function groupIdOfTrashedOption(int $optionId): ?int;

    public function restoreOption(int $optionId): void;

    /** Deletes an option that is in the Trash for good, with its prices. */
    public function forceDeleteOption(int $optionId): void;
}
