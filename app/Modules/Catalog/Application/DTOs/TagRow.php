<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

use App\Modules\Catalog\Domain\Enums\TagType;

/**
 * One tag on the admin list and edit page.
 */
final readonly class TagRow
{
    /**
     * @param  int  $productCount  products (not deleted) with this tag
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public TagType $type,
        public ?string $icon,
        public int $sort,
        public bool $isActive,
        public int $productCount,
    ) {}
}
