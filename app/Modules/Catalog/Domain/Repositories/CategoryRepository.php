<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Repositories;

use App\Modules\Catalog\Domain\Entities\Category;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

interface CategoryRepository
{
    /** A category that is not deleted, or null. */
    public function find(int $id): ?Category;

    /** Inserts when it has no id yet, otherwise updates. Returns the id. */
    public function save(Category $category): int;

    /** Soft delete. Call it only when hasProducts() and hasChildren() are false. */
    public function delete(int $id): void;

    public function restore(int $id): void;

    /** Looks at deleted rows too. $ignoreId skips the category that is being edited. */
    public function slugExists(Slug $slug, ?int $ignoreId = null): bool;

    /** True when a product that is not deleted belongs to this category. */
    public function hasProducts(int $id): bool;

    /** True when a sub-category that is not deleted has this category as parent. */
    public function hasChildren(int $id): bool;

    /**
     * A category that is in the Trash, or null. Used before deleting forever.
     */
    public function findTrashed(int $id): ?Category;

    /**
     * The position after the last category (not deleted) with the same parent.
     * Pass null for the top level.
     */
    public function nextSortOrder(?int $parentId): SortOrder;

    /**
     * True when any product or sub-category points to this category, also ones in the Trash.
     * The database refuses to delete such a row for good (foreign keys).
     */
    public function isUsedIncludingTrash(int $id): bool;

    /** Deletes a category that is in the Trash for good. */
    public function forceDelete(int $id): void;
}
