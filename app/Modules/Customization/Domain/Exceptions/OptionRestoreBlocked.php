<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\DomainException;

/**
 * An option cannot come back from the Trash yet. The message tells the admin what to do first.
 */
final class OptionRestoreBlocked extends DomainException
{
    public static function groupInTrash(string $option): self
    {
        return new self("\"{$option}\" cannot be restored while its option group is in the Trash. Restore the group first.");
    }

    public static function nameTaken(string $option): self
    {
        return new self("\"{$option}\" cannot be restored: its group has another option with this name. Rename that one first.");
    }
}
