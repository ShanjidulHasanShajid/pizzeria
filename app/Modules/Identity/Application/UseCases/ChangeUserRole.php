<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Identity\Application\DTOs\ChangeRoleData;
use App\Modules\Identity\Domain\Repositories\UserRepository;
use App\Modules\Identity\Domain\Services\UserProtection;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

final class ChangeUserRole
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserProtection $protection,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(ChangeRoleData $data): void
    {
        $this->transactions->run(function () use ($data): void {
            $user = $this->users->find($data->userId);

            if ($user === null || $user->isDeleted) {
                throw EntityNotFound::withId('User', $data->userId);
            }

            $this->protection->assertCanChangeRole(
                $data->actorId,
                $user,
                $data->role,
                $this->users->activeSuperAdminCount(),
            );

            $this->users->changeRole($data->userId, $data->role);
        });
    }
}
