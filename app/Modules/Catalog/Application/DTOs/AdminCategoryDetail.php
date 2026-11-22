<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * Everything the category edit form shows.
 */
final readonly class AdminCategoryDetail
{
    /**
     * @param  ?string  $imagePath  thumbnail (or original) on the public disk, or null
     * @param  int  $childCount  sub-categories that are not deleted; above 0 means it must stay top level
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?int $parentId,
        public int $kitchenStationId,
        public ?string $description,
        public ?string $imagePath,
        public bool $isActive,
        public bool $isFeatured,
        public bool $showInMegaMenu,
        public int $childCount,
        public int $productCount,
    ) {}
}
