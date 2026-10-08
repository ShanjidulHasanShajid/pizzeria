<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

use App\Modules\Identity\Domain\Enums\UserRole;

final readonly class CreateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public string $password,
        public UserRole $role,
    ) {}
}
