<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

use App\Modules\Identity\Domain\Enums\UserRole;
use DateTimeImmutable;

/**
 * One row of an admin list (the "read DTO": built for display, not for rules).
 */
final readonly class UserListItem
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $phone,
        public UserRole $role,
        public bool $isBlocked,
        public bool $isDeleted,
        public DateTimeImmutable $createdAt,
    ) {}
}
