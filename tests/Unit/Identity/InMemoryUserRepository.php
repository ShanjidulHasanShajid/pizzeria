<?php

declare(strict_types=1);

namespace Tests\Unit\Identity;

use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Domain\Repositories\UserRepository;
use App\Modules\Identity\Domain\ValueObjects\NewUser;
use App\Modules\Identity\Domain\ValueObjects\UserRecord;
use App\Modules\Shared\Domain\ValueObjects\EmailAddress;
use App\Modules\Shared\Domain\ValueObjects\PhoneNumber;

/**
 * A repository that keeps users in an array. Lets unit tests run without a database.
 */
final class InMemoryUserRepository implements UserRepository
{
    /** @var array<int, UserRecord> */
    public array $records = [];

    /** @var array<int, string> */
    public array $passwords = [];

    private int $nextId = 1;

    public function add(UserRole $role, bool $blocked = false, bool $deleted = false): UserRecord
    {
        $id = $this->nextId++;

        return $this->records[$id] = new UserRecord($id, 'User '.$id, "user{$id}@example.com", null, $role, $blocked, $deleted);
    }

    public function find(int $id): ?UserRecord
    {
        return $this->records[$id] ?? null;
    }

    public function activeSuperAdminCount(): int
    {
        return count(array_filter(
            $this->records,
            fn (UserRecord $r): bool => $r->role === UserRole::SuperAdmin && ! $r->isBlocked && ! $r->isDeleted,
        ));
    }

    public function create(NewUser $user): int
    {
        $id = $this->nextId++;
        $this->records[$id] = new UserRecord($id, $user->name, $user->email->value(), $user->phone?->value(), $user->role, false, false);
        $this->passwords[$id] = $user->password;

        return $id;
    }

    public function update(int $id, string $name, EmailAddress $email, ?PhoneNumber $phone, ?string $newPassword): void
    {
        $old = $this->records[$id];
        $this->records[$id] = new UserRecord($id, $name, $email->value(), $phone?->value(), $old->role, $old->isBlocked, $old->isDeleted);

        if ($newPassword !== null) {
            $this->passwords[$id] = $newPassword;
        }
    }

    public function changePassword(int $id, string $password): void
    {
        $this->passwords[$id] = $password;
    }

    public function changeRole(int $id, UserRole $role): void
    {
        $this->replace($id, role: $role);
    }

    public function setBlocked(int $id, bool $blocked): void
    {
        $this->replace($id, blocked: $blocked);
    }

    public function delete(int $id): void
    {
        $this->replace($id, deleted: true);
    }

    public function restore(int $id): void
    {
        $this->replace($id, deleted: false);
    }

    private function replace(int $id, ?UserRole $role = null, ?bool $blocked = null, ?bool $deleted = null): void
    {
        $old = $this->records[$id];
        $this->records[$id] = new UserRecord(
            $id, $old->name, $old->email, $old->phone,
            $role ?? $old->role, $blocked ?? $old->isBlocked, $deleted ?? $old->isDeleted,
        );
    }
}
