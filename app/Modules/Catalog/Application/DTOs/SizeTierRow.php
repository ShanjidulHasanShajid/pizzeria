<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * One size tier on the admin list and edit page.
 */
final readonly class SizeTierRow
{
    /**
     * @param  int  $variantCount  variants (not deleted) linked to this size; above 0 blocks deleting
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public int $sort,
        public bool $isActive,
        public int $variantCount,
    ) {}
}
