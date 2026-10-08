<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Identity\Application\DTOs\UserActionData;
use App\Modules\Identity\Domain\Repositories\UserRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

final class RestoreUser
{
    public function __construct(private readonly UserRepository $users) {}

    public function handle(UserActionData $data): void
    {
        $user = $this->users->find($data->userId);

        if ($user === null || ! $user->isDeleted) {
            throw EntityNotFound::withId('User', $data->userId);
        }

        $this->users->restore($data->userId);
    }
}
