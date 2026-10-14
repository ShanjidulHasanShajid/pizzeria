<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * One row of the admin category table.
 */
final readonly class AdminCategoryRow
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?int $parentId,
        public ?string $parentName,
        public string $kitchenStationName,
        public int $productCount,
        public int $sort,
        public bool $isActive,
        public bool $isFeatured,
        public bool $showInMegaMenu,
    ) {}
}
