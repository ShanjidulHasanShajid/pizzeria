<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

use App\Modules\Identity\Domain\Enums\UserRole;

final readonly class UserListFilter
{
    /**
     * @param  string  $status  all, active, blocked or deleted
     */
    public function __construct(
        public string $search = '',
        public ?UserRole $role = null,
        public string $status = 'all',
        public int $page = 1,
        public int $perPage = 15,
    ) {}
}
