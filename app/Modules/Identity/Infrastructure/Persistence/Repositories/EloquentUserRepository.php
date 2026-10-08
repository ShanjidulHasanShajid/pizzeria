<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Repositories;

use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Domain\Repositories\UserRepository;
use App\Modules\Identity\Domain\ValueObjects\NewUser;
use App\Modules\Identity\Domain\ValueObjects\UserRecord;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Shared\Domain\ValueObjects\EmailAddress;
use App\Modules\Shared\Domain\ValueObjects\PhoneNumber;
use Illuminate\Support\Str;

final class EloquentUserRepository implements UserRepository
{
    public function find(int $id): ?UserRecord
    {
        $user = User::withTrashed()->find($id);

        return $user === null ? null : $this->toRecord($user);
    }

    public function activeSuperAdminCount(): int
    {
        // Soft-deleted rows are excluded automatically.
        return User::query()
            ->where('role', UserRole::SuperAdmin->value)
            ->where('is_blocked', false)
            ->count();
    }

    public function create(NewUser $user): int
    {
        $model = new User([
            'name' => $user->name,
            'email' => $user->email->value(),
            'phone' => $user->phone?->value(),
            'password' => $user->password, // hashed by the model's cast
        ]);
        $model->role = $user->role; // set on purpose, not mass-assigned
        $model->save();

        return $model->id;
    }

    public function update(int $id, string $name, EmailAddress $email, ?PhoneNumber $phone, ?string $newPassword): void
    {
        $model = User::query()->findOrFail($id);
        $model->fill([
            'name' => $name,
            'email' => $email->value(),
            'phone' => $phone?->value(),
        ]);

        if ($newPassword !== null) {
            $model->password = $newPassword;
        }

        $model->save();
    }

    public function changePassword(int $id, string $password): void
    {
        $model = User::query()->findOrFail($id);
        $model->password = $password;
        // A new remember-me token signs the user out on every other device.
        $model->remember_token = Str::random(60);
        $model->save();
    }

    public function changeRole(int $id, UserRole $role): void
    {
        $model = User::withTrashed()->findOrFail($id);
        $model->role = $role;
        $model->save();
    }

    public function setBlocked(int $id, bool $blocked): void
    {
        $model = User::withTrashed()->findOrFail($id);
        $model->is_blocked = $blocked;
        $model->save();
    }

    public function delete(int $id): void
    {
        User::query()->findOrFail($id)->delete();
    }

    public function restore(int $id): void
    {
        User::onlyTrashed()->findOrFail($id)->restore();
    }

    private function toRecord(User $user): UserRecord
    {
        return new UserRecord(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            phone: $user->phone,
            role: $user->role,
            isBlocked: $user->is_blocked,
            isDeleted: $user->trashed(),
        );
    }
}
