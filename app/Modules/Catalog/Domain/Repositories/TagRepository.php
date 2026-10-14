<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Repositories;

use App\Modules\Catalog\Domain\Entities\Tag;
use App\Modules\Shared\Domain\ValueObjects\Slug;

interface TagRepository
{
    /** A tag that is not deleted, or null. */
    public function find(int $id): ?Tag;

    /** Inserts when it has no id yet, otherwise updates. Returns the id. */
    public function save(Tag $tag): int;

    /** Soft delete. Products keep the link; deleted tags are simply not shown. */
    public function delete(int $id): void;

    public function restore(int $id): void;

    /** Looks at deleted rows too. $ignoreId skips the tag that is being edited. */
    public function slugExists(Slug $slug, ?int $ignoreId = null): bool;
}
