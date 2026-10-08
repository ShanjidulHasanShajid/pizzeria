<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Domain\Repositories\UserRepository;
use App\Modules\Identity\Domain\ValueObjects\NewUser;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Shared\Domain\ValueObjects\EmailAddress;
use App\Modules\Shared\Domain\ValueObjects\PhoneNumber;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->repository = app(UserRepository::class);
});

it('creates a user with a hashed password and the given role', function (): void {
    $id = $this->repository->create(new NewUser(
        'Sara', EmailAddress::fromString('sara@example.com'), PhoneNumber::fromString('01712345678'), 'secret123', UserRole::Staff,
    ));

    $model = User::query()->findOrFail($id);

    expect($model->role)->toBe(UserRole::Staff)
        ->and($model->phone)->toBe('01712345678')
        ->and(Hash::check('secret123', $model->password))->toBeTrue();
});

it('finds soft-deleted users and reports them as deleted', function (): void {
    $user = User::factory()->create();
    $this->repository->delete($user->id);

    $record = $this->repository->find($user->id);

    expect($record)->not->toBeNull()->and($record->isDeleted)->toBeTrue();

    $this->repository->restore($user->id);

    expect($this->repository->find($user->id)->isDeleted)->toBeFalse();
});

it('counts only active super admins', function (): void {
    User::factory()->superAdmin()->create();
    User::factory()->superAdmin()->blocked()->create();
    $deleted = User::factory()->superAdmin()->create();
    $deleted->delete();
    User::factory()->admin()->create();

    expect($this->repository->activeSuperAdminCount())->toBe(1);
});

it('keeps the old password when update gets null', function (): void {
    $user = User::factory()->create();
    $oldHash = $user->password;

    $this->repository->update($user->id, 'New Name', EmailAddress::fromString($user->email), null, null);

    expect($user->fresh()->name)->toBe('New Name')
        ->and($user->fresh()->phone)->toBeNull()
        ->and($user->fresh()->password)->toBe($oldHash);
});

it('returns null for an unknown id', function (): void {
    expect($this->repository->find(424242))->toBeNull();
});
