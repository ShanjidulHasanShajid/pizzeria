<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * One page of the admin product table.
 */
final readonly class AdminProductPage
{
    /**
     * @param  list<AdminProductRow>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}
}
