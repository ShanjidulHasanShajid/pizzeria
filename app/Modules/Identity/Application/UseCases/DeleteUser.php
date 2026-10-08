<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Identity\Application\DTOs\UserActionData;
use App\Modules\Identity\Domain\Repositories\UserRepository;
use App\Modules\Identity\Domain\Services\UserProtection;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Soft delete: the row stays (orders keep pointing at it) but the person can no longer log in.
 */
final class DeleteUser
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserProtection $protection,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(UserActionData $data): void
    {
        $this->transactions->run(function () use ($data): void {
            $user = $this->users->find($data->userId);

            if ($user === null || $user->isDeleted) {
                throw EntityNotFound::withId('User', $data->userId);
            }

            $this->protection->assertCanDelete($data->actorId, $user, $this->users->activeSuperAdminCount());
            $this->users->delete($data->userId);
        });
    }
}
