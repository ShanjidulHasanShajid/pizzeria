<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\DomainException;

/**
 * Something cannot go to the Trash because other records still use it.
 * The message tells the admin what to change first.
 */
final class CatalogItemInUse extends DomainException
{
    public static function kitchenStation(string $name): self
    {
        return new self("\"{$name}\" is used by categories. Give those categories another kitchen station first.");
    }

    public static function sizeTier(string $name): self
    {
        return new self("\"{$name}\" is used by product variants. Remove it from those variants first.");
    }
}
