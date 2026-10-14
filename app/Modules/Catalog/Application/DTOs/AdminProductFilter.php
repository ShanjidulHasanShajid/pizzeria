<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

use App\Modules\Catalog\Domain\Enums\ProductType;

/**
 * The filters above the admin product table.
 */
final readonly class AdminProductFilter
{
    /**
     * @param  string  $status  all, published, unpublished, sold_out or trashed
     */
    public function __construct(
        public string $search = '',
        public ?int $categoryId = null,
        public ?ProductType $type = null,
        public ?int $tagId = null,
        public string $status = 'all',
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
