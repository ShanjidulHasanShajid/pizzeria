<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Repositories;

use App\Modules\Catalog\Domain\Entities\Product;
use App\Modules\Shared\Domain\ValueObjects\Slug;

interface ProductRepository
{
    /** A product that is not deleted, with its variants, images and tag ids, or null. */
    public function find(int $id): ?Product;

    /**
     * Products that are not deleted. Unknown or deleted ids are skipped.
     *
     * @param  list<int>  $ids
     * @return list<Product>
     */
    public function findByIds(array $ids): array;

    /**
     * Saves the whole aggregate in one go and returns the product id:
     * new variants and images are inserted, changed ones updated, removed ones deleted
     * (variants soft-deleted, images deleted), and the tag links replaced.
     */
    public function save(Product $product): int;

    /** Soft delete. Old orders keep their snapshots, so nothing else changes. */
    public function delete(int $id): void;

    public function restore(int $id): void;

    /** Looks at deleted rows too. $ignoreId skips the product that is being edited. */
    public function slugExists(Slug $slug, ?int $ignoreId = null): bool;
}
