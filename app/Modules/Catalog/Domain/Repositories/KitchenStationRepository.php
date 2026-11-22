<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Repositories;

use App\Modules\Catalog\Domain\Entities\KitchenStation;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * The saved kitchen stations. The Eloquent version comes in batch 3.
 */
interface KitchenStationRepository
{
    /** A station that is not deleted, or null. */
    public function find(int $id): ?KitchenStation;

    /** Inserts the station when it has no id yet, otherwise updates it. Returns the id. */
    public function save(KitchenStation $station): int;

    /** Soft delete: the row goes to the Trash and can be restored. */
    public function delete(int $id): void;

    public function restore(int $id): void;

    /** True when a category that is not deleted uses this station. A station in use cannot be deleted. */
    public function isUsedByCategories(int $id): bool;

    /** A station that is in the Trash, or null. */
    public function findTrashed(int $id): ?KitchenStation;

    /**
     * True when any category uses this station, also a category in the Trash.
     * The database refuses to delete such a row for good (foreign key).
     */
    public function isUsedIncludingTrash(int $id): bool;

    /** Deletes a station that is in the Trash for good. */
    public function forceDelete(int $id): void;

    /** The position after the last item that is not deleted: 10 for the first one, then +10. */
    public function nextSortOrder(): SortOrder;
}
