<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Repositories;

use App\Modules\Catalog\Domain\Entities\SizeTier;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

interface SizeTierRepository
{
    /** A size tier that is not deleted, or null. */
    public function find(int $id): ?SizeTier;

    /** Inserts when it has no id yet, otherwise updates. Returns the id. */
    public function save(SizeTier $sizeTier): int;

    /** Soft delete. */
    public function delete(int $id): void;

    public function restore(int $id): void;

    /**
     * Looks at deleted rows too, because slugs stay unique in the Trash.
     * $ignoreId skips the size tier that is being edited, so it can keep its own slug.
     */
    public function slugExists(Slug $slug, ?int $ignoreId = null): bool;

    /** True when a variant that is not deleted uses this size. Option prices are asked through the SizeTierPrices port. */
    public function isInUse(int $id): bool;

    /** A size tier that is in the Trash, or null. */
    public function findTrashed(int $id): ?SizeTier;

    /**
     * True when any variant uses this size: also removed variants (kept for old orders)
     * and variants of products in the Trash. The database refuses to delete such a row for good.
     */
    public function isUsedIncludingTrash(int $id): bool;

    /** Deletes a size tier that is in the Trash for good. */
    public function forceDelete(int $id): void;

    /** The position after the last item that is not deleted: 10 for the first one, then +10. */
    public function nextSortOrder(): SortOrder;
}
