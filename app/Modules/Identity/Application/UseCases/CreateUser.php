<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Identity\Application\DTOs\CreateUserData;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Domain\Repositories\UserRepository;
use App\Modules\Identity\Domain\ValueObjects\NewUser;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\EmailAddress;
use App\Modules\Shared\Domain\ValueObjects\PhoneNumber;

/**
 * Creates a staff or admin account from the admin screen.
 * (A super admin comes from the seeder or from a role change.)
 */
final class CreateUser
{
    public function __construct(private readonly UserRepository $users) {}

    public function handle(CreateUserData $data): int
    {
        if (! in_array($data->role, [UserRole::Staff, UserRole::Admin], true)) {
            throw ValidationFailed::forField('role', 'Choose staff or admin.');
        }

        return $this->users->create(new NewUser(
            name: trim($data->name),
            email: EmailAddress::fromString($data->email),
            phone: $data->phone === null ? null : PhoneNumber::fromString($data->phone),
            password: $data->password,
            role: $data->role,
        ));
    }
}
