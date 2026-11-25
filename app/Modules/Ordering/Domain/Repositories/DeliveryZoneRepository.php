<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\Repositories;

use App\Modules\Ordering\Domain\Entities\DeliveryZone;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * The saved delivery zones.
 */
interface DeliveryZoneRepository
{
    /** A zone that is not deleted, or null. */
    public function find(int $id): ?DeliveryZone;

    /** Inserts the zone when it has no id yet, otherwise updates it. Returns the id. */
    public function save(DeliveryZone $zone): int;

    /** Soft delete: the row goes to the Trash and can be restored. */
    public function delete(int $id): void;

    public function restore(int $id): void;

    /** Deletes a zone that is in the Trash for good. */
    public function forceDelete(int $id): void;

    /** The position after the last zone that is not deleted: 10 for the first one, then +10. */
    public function nextSortOrder(): SortOrder;
}
