<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Identity\Application\DTOs\UpdateUserData;
use App\Modules\Identity\Domain\Repositories\UserRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\ValueObjects\EmailAddress;
use App\Modules\Shared\Domain\ValueObjects\PhoneNumber;

final class UpdateUser
{
    public function __construct(private readonly UserRepository $users) {}

    public function handle(UpdateUserData $data): void
    {
        $user = $this->users->find($data->userId);

        if ($user === null || $user->isDeleted) {
            throw EntityNotFound::withId('User', $data->userId);
        }

        $this->users->update(
            $data->userId,
            trim($data->name),
            EmailAddress::fromString($data->email),
            $data->phone === null ? null : PhoneNumber::fromString($data->phone),
            $data->password,
        );
    }
}
