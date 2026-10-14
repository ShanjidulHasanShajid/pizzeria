<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Repositories;

use App\Modules\Catalog\Domain\Entities\SizeTier;
use App\Modules\Shared\Domain\ValueObjects\Slug;

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

    /** True when a variant that is not deleted uses this size. Phase 8 adds option prices to this check. */
    public function isInUse(int $id): bool;
}
