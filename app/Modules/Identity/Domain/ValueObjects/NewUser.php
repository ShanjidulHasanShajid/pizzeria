<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObjects;

use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Shared\Domain\ValueObjects\EmailAddress;
use App\Modules\Shared\Domain\ValueObjects\PhoneNumber;

/**
 * A user that is about to be created. The email and phone are already validated
 * and normalized because they are value objects.
 */
final readonly class NewUser
{
    public function __construct(
        public string $name,
        public EmailAddress $email,
        public ?PhoneNumber $phone,
        public string $password,
        public UserRole $role,
    ) {}
}
