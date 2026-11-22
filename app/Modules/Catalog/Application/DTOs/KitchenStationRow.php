<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * One kitchen station on the admin list and edit page.
 */
final readonly class KitchenStationRow
{
    /**
     * @param  int  $categoryCount  categories (not deleted) that use this station; above 0 blocks deleting
     */
    public function __construct(
        public int $id,
        public string $name,
        public int $sort,
        public bool $isActive,
        public int $categoryCount,
    ) {}
}
