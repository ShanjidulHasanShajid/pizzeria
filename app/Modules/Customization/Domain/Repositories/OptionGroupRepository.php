<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\Repositories;

use App\Modules\Customization\Domain\Entities\OptionGroup;

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
}
