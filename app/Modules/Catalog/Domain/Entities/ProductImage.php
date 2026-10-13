<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Entities;

use App\Modules\Catalog\Domain\ValueObjects\ImagePath;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * One picture of a product. Part of the Product aggregate.
 * Product makes sure exactly one image is the main one when there are images.
 */
final class ProductImage
{
    private function __construct(
        private ?int $id,
        private ImagePath $path,
        private bool $isMain,
        private SortOrder $sort,
    ) {}

    public static function create(ImagePath $path, SortOrder $sort): self
    {
        return new self(null, $path, false, $sort);
    }

    public static function reconstitute(int $id, ImagePath $path, bool $isMain, SortOrder $sort): self
    {
        return new self($id, $path, $isMain, $sort);
    }

    public function moveTo(SortOrder $sort): void
    {
        $this->sort = $sort;
    }

    /**
     * Only Product calls this, right after clearing the old main image.
     */
    public function markAsMain(): void
    {
        $this->isMain = true;
    }

    /**
     * Only Product calls this.
     */
    public function unmarkAsMain(): void
    {
        $this->isMain = false;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function path(): ImagePath
    {
        return $this->path;
    }

    public function isMain(): bool
    {
        return $this->isMain;
    }

    public function sort(): SortOrder
    {
        return $this->sort;
    }
}
