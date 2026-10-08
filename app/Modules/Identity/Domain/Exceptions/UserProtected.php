<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\DomainException;

/**
 * A rule that protects super admins (docs/business-rules.md section 2).
 */
final class UserProtected extends DomainException
{
    public static function cannotTargetYourself(string $action): self
    {
        return new self("You cannot {$action} your own account.");
    }

    public static function lastSuperAdmin(string $action): self
    {
        return new self("You cannot {$action} the last super admin.");
    }
}
