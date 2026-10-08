<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Domain\Exceptions\UserProtected;
use App\Modules\Identity\Domain\Services\UserProtection;
use App\Modules\Identity\Domain\ValueObjects\UserRecord;

function userRecord(int $id, UserRole $role, bool $blocked = false): UserRecord
{
    return new UserRecord($id, 'User '.$id, "u{$id}@example.com", null, $role, $blocked, false);
}

it('does not let a super admin block themselves', function (): void {
    (new UserProtection)->assertCanBlock(1, userRecord(1, UserRole::SuperAdmin), 2);
})->throws(UserProtected::class, 'You cannot block your own account.');

it('does not let a super admin delete themselves', function (): void {
    (new UserProtection)->assertCanDelete(1, userRecord(1, UserRole::SuperAdmin), 2);
})->throws(UserProtected::class, 'You cannot delete your own account.');

it('does not let a super admin demote themselves', function (): void {
    (new UserProtection)->assertCanChangeRole(1, userRecord(1, UserRole::SuperAdmin), UserRole::Admin, 2);
})->throws(UserProtected::class, 'You cannot demote your own account.');

it('never removes the last active super admin', function (): void {
    (new UserProtection)->assertCanBlock(99, userRecord(1, UserRole::SuperAdmin), 1);
})->throws(UserProtected::class, 'You cannot block the last super admin.');

it('lets a super admin demote another super admin when two exist', function (): void {
    (new UserProtection)->assertCanChangeRole(1, userRecord(2, UserRole::SuperAdmin), UserRole::Admin, 2);

    expect(true)->toBeTrue();
});

it('allows promoting someone to super admin', function (): void {
    (new UserProtection)->assertCanChangeRole(1, userRecord(2, UserRole::Admin), UserRole::SuperAdmin, 1);

    expect(true)->toBeTrue();
});

it('allows blocking an ordinary customer', function (): void {
    (new UserProtection)->assertCanBlock(1, userRecord(5, UserRole::Customer), 1);

    expect(true)->toBeTrue();
});
