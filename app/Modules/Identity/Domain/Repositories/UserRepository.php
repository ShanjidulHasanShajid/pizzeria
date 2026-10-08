<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Repositories;

use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Domain\ValueObjects\NewUser;
use App\Modules\Identity\Domain\ValueObjects\UserRecord;
use App\Modules\Shared\Domain\ValueObjects\EmailAddress;
use App\Modules\Shared\Domain\ValueObjects\PhoneNumber;

interface UserRepository
{
    /** Finds a user, including soft-deleted ones. */
    public function find(int $id): ?UserRecord;

    /** Super admins who are not blocked and not deleted. */
    public function activeSuperAdminCount(): int;

    /** Saves a new user and returns its id. The password is hashed by the implementation. */
    public function create(NewUser $user): int;

    /** Passing null as $newPassword keeps the current password. */
    public function update(int $id, string $name, EmailAddress $email, ?PhoneNumber $phone, ?string $newPassword): void;

    public function changePassword(int $id, string $password): void;

    public function changeRole(int $id, UserRole $role): void;

    public function setBlocked(int $id, bool $blocked): void;

    /** Soft delete. */
    public function delete(int $id): void;

    public function restore(int $id): void;
}
