<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

final readonly class UserPage
{
    /**
     * @param  list<UserListItem>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}
}
