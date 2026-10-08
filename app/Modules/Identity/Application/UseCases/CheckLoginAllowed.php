<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Identity\Domain\Exceptions\AccountBlocked;
use App\Modules\Identity\Domain\Repositories\UserRepository;

/**
 * Called after a successful password check, and again on every request of a
 * logged-in user. Throws AccountBlocked when the account may not be used.
 */
final class CheckLoginAllowed
{
    public function __construct(private readonly UserRepository $users) {}

    public function handle(int $userId): void
    {
        $user = $this->users->find($userId);

        if ($user === null || $user->isDeleted || $user->isBlocked) {
            throw AccountBlocked::create();
        }
    }
}
