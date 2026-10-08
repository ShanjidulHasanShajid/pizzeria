<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

final readonly class ChangePasswordData
{
    public function __construct(
        public int $userId,
        public string $password,
    ) {}
}
