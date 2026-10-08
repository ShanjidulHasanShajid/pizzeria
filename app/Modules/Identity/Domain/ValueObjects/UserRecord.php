<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObjects;

use App\Modules\Identity\Domain\Enums\UserRole;

/**
 * What the business rules need to know about one user. Plain PHP: no Eloquent.
 */
final readonly class UserRecord
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $phone,
        public UserRole $role,
        public bool $isBlocked,
        public bool $isDeleted,
    ) {}
}
