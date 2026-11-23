<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\Repositories;

use App\Modules\Content\Domain\ValueObjects\Location;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

interface LocationRepository
{
    /**
     * Locations that are not deleted, in sort order.
     *
     * @return list<Location>
     */
    public function all(): array;

    /** A location that is not deleted, or null. */
    public function find(int $id): ?Location;

    /** Inserts when it has no id yet, otherwise updates. Returns the id. */
    public function save(Location $location): int;

    /** Soft delete. */
    public function delete(int $id): void;

    public function restore(int $id): void;

    /** Removes a location of the Trash for good. */
    public function forceDelete(int $id): void;

    /** The position after the last location that is not deleted: 10 for the first one, then +10. */
    public function nextSortOrder(): SortOrder;
}
