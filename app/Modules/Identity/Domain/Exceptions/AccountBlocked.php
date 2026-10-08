<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\DomainException;

final class AccountBlocked extends DomainException
{
    public static function create(): self
    {
        return new self('Your account has been blocked. Please contact the restaurant for help.');
    }
}
