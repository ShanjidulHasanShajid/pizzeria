<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

final readonly class UserActionData
{
    public function __construct(
        public int $actorId,
        public int $userId,
    ) {}
}
