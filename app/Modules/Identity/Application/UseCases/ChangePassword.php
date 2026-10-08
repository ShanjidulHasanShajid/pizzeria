<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Identity\Application\DTOs\ChangePasswordData;
use App\Modules\Identity\Domain\Repositories\UserRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Used by "reset password". Saves the new password (hashed by the repository).
 */
final class ChangePassword
{
    public function __construct(private readonly UserRepository $users) {}

    public function handle(ChangePasswordData $data): void
    {
        if ($this->users->find($data->userId) === null) {
            throw EntityNotFound::withId('User', $data->userId);
        }

        $this->users->changePassword($data->userId, $data->password);
    }
}
