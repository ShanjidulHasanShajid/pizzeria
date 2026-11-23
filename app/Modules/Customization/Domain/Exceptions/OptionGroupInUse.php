<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\DomainException;

/**
 * An option group cannot go to the Trash while products offer it.
 */
final class OptionGroupInUse extends DomainException
{
    public static function attachedToProducts(string $name): self
    {
        return new self("\"{$name}\" is attached to products (maybe in the Trash). Detach it from them first.");
    }
}
