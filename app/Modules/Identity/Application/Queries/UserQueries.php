<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Queries;

use App\Modules\Identity\Application\DTOs\UserListFilter;
use App\Modules\Identity\Application\DTOs\UserListItem;
use App\Modules\Identity\Application\DTOs\UserPage;

/**
 * Read side of Identity. Returns read DTOs, never entities.
 */
interface UserQueries
{
    /** One user (also soft-deleted ones), or null. */
    public function find(int $id): ?UserListItem;

    /** Staff, admins and super admins. */
    public function paginateStaff(UserListFilter $filter): UserPage;

    /** Customers only. */
    public function paginateCustomers(UserListFilter $filter): UserPage;
}
