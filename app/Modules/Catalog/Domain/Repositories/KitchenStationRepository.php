<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Repositories;

use App\Modules\Catalog\Domain\Entities\KitchenStation;

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
}
