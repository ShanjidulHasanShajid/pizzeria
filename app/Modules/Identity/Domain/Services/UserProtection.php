<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Services;

use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Domain\Exceptions\UserProtected;
use App\Modules\Identity\Domain\ValueObjects\UserRecord;

/**
 * Rules: nobody can block, delete or demote themselves, and the last
 * active super admin can never be removed.
 * Pure PHP: the caller passes in the numbers it needs.
 */
final class UserProtection
{
    public function assertCanBlock(int $actorId, UserRecord $target, int $activeSuperAdmins): void
    {
        $this->assertCanRemove($actorId, $target, $activeSuperAdmins, 'block');
    }

    public function assertCanDelete(int $actorId, UserRecord $target, int $activeSuperAdmins): void
    {
        $this->assertCanRemove($actorId, $target, $activeSuperAdmins, 'delete');
    }

    public function assertCanChangeRole(int $actorId, UserRecord $target, UserRole $newRole, int $activeSuperAdmins): void
    {
        // Only taking the super admin power away is restricted.
        if (! $target->role->isSuperAdmin() || $newRole->isSuperAdmin()) {
            return;
        }

        $this->assertCanRemove($actorId, $target, $activeSuperAdmins, 'demote');
    }

    private function assertCanRemove(int $actorId, UserRecord $target, int $activeSuperAdmins, string $action): void
    {
        if ($actorId === $target->id) {
            throw UserProtected::cannotTargetYourself($action);
        }

        if ($target->role->isSuperAdmin() && ! $target->isBlocked && $activeSuperAdmins <= 1) {
            throw UserProtected::lastSuperAdmin($action);
        }
    }
}
