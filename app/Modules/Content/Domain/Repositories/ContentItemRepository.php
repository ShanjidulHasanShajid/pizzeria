<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\Repositories;

use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Domain\ValueObjects\ContentItem;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

interface ContentItemRepository
{
    /**
     * Items that are not deleted, in sort order.
     *
     * @return list<ContentItem>
     */
    public function all(ContentType $type): array;

    /** An item that is not deleted, or null. */
    public function find(ContentType $type, int $id): ?ContentItem;

    /** Inserts when it has no id yet, otherwise updates. Returns the id. */
    public function save(ContentItem $item): int;

    /** Soft delete. */
    public function delete(ContentType $type, int $id): void;

    public function restore(ContentType $type, int $id): void;

    /** An item in the Trash, or null. */
    public function findDeleted(ContentType $type, int $id): ?ContentItem;

    /** Removes an item of the Trash for good. */
    public function forceDelete(ContentType $type, int $id): void;

    /** The position after the last item that is not deleted: 10 for the first one, then +10. */
    public function nextSortOrder(ContentType $type): SortOrder;
}
