<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\DomainException;

/**
 * A branch that other records still belong to cannot be deleted for good.
 */
final class LocationInUse extends DomainException
{
    public static function byDeliveryZones(): self
    {
        return new self('This location cannot be deleted forever: delivery zones belong to it (look in the Trash too). Give them another branch or delete them forever first.');
    }
}
