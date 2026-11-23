<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\DomainException;

/**
 * A change would break a rule of an option group (for example two options with the same name).
 */
final class InvalidOptionGroup extends DomainException
{
    public static function duplicateOptionName(string $name): self
    {
        return new self("There is already an option called \"{$name}\" in this group.");
    }

    public static function unknownOption(int $optionId): self
    {
        return new self("Option {$optionId} does not belong to this group.");
    }

    public static function orderDoesNotMatch(): self
    {
        return new self('The list must contain every option of the group once. Reload the page and try again.');
    }
}
