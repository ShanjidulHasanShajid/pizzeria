<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

use App\Modules\Identity\Domain\Enums\UserRole;

final readonly class ChangeRoleData
{
    public function __construct(
        public int $actorId,
        public int $userId,
        public UserRole $role,
    ) {}
}
