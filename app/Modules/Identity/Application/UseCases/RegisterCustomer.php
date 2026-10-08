<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Identity\Application\DTOs\RegisterCustomerData;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Domain\Repositories\UserRepository;
use App\Modules\Identity\Domain\ValueObjects\NewUser;
use App\Modules\Shared\Domain\ValueObjects\EmailAddress;
use App\Modules\Shared\Domain\ValueObjects\PhoneNumber;

final class RegisterCustomer
{
    public function __construct(private readonly UserRepository $users) {}

    /** Returns the id of the new customer. */
    public function handle(RegisterCustomerData $data): int
    {
        return $this->users->create(new NewUser(
            name: trim($data->name),
            email: EmailAddress::fromString($data->email),
            phone: PhoneNumber::fromString($data->phone),
            password: $data->password,
            role: UserRole::Customer, // always. The browser can never choose a role.
        ));
    }
}
