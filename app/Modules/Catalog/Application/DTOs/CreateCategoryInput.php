<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

final readonly class CreateCategoryInput
{
    /**
     * @param  ?string  $slug  null or empty: made from the name
     * @param  ?int  $parentId  null: a top-level category
     */
    public function __construct(
        public string $name,
        public ?string $slug,
        public ?int $parentId,
        public int $kitchenStationId,
        public ?string $description,
        public bool $isActive,
        public bool $isFeatured,
        public bool $showInMegaMenu,
        public ?ImageUpload $image,
    ) {}
}
