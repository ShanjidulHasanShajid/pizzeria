<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

final readonly class UpdateUserData
{
    public function __construct(
        public int $userId,
        public string $name,
        public string $email,
        public ?string $phone,
        public ?string $password,
    ) {}
}
