<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

final readonly class UpdateCategoryInput
{
    /**
     * @param  ?string  $slug  null or empty: keep the current slug
     * @param  ?int  $parentId  null: top level
     * @param  ?ImageUpload  $newImage  replaces the current image (the old files are deleted)
     * @param  bool  $removeImage  true: no image any more (ignored when there is a new image)
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $slug,
        public ?int $parentId,
        public int $kitchenStationId,
        public ?string $description,
        public bool $isActive,
        public bool $isFeatured,
        public bool $showInMegaMenu,
        public ?ImageUpload $newImage,
        public bool $removeImage,
    ) {}
}
