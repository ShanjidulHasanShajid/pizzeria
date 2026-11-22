<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\DomainException;

/**
 * Something cannot come back from the Trash while the record it belongs to is still there.
 * The message tells the admin what to restore first.
 */
final class RestoreBlocked extends DomainException
{
    public static function categoryInTrash(string $product, string $category): self
    {
        return new self("\"{$product}\" cannot be restored while its category \"{$category}\" is in the Trash. Restore \"{$category}\" first.");
    }

    public static function parentCategoryInTrash(string $category, string $parent): self
    {
        return new self("\"{$category}\" cannot be restored while its parent category \"{$parent}\" is in the Trash. Restore \"{$parent}\" first.");
    }

    public static function kitchenStationInTrash(string $category, string $station): self
    {
        return new self("\"{$category}\" cannot be restored while its kitchen station \"{$station}\" is in the Trash. Restore \"{$station}\" first.");
    }
}
