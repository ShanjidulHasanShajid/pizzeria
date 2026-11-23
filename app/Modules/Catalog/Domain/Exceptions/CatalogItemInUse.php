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

    public static function sizeTierInOptionPrices(string $name): self
    {
        return new self("\"{$name}\" is used by option prices. Empty its column in the price matrix of every option group first.");
    }

    public static function sizeTierStillInOptionPrices(string $name): self
    {
        return new self("\"{$name}\" cannot be deleted forever: option prices still use it, also of deleted or flat-priced options. Delete those options forever first, or keep the size in the Trash.");
    }

    public static function categoryWithProducts(string $name): self
    {
        return new self("\"{$name}\" still has products. Move them to another category or delete them first.");
    }

    public static function categoryWithSubCategories(string $name): self
    {
        return new self("\"{$name}\" has sub-categories. Move or delete them first.");
    }

    public static function categoryStillReferenced(string $name): self
    {
        return new self("\"{$name}\" cannot be deleted forever: products or sub-categories still belong to it, maybe in the Trash. Delete those forever first.");
    }

    public static function kitchenStationStillReferenced(string $name): self
    {
        return new self("\"{$name}\" cannot be deleted forever: categories still use it, maybe in the Trash. Give them another kitchen station or delete them forever first.");
    }

    public static function sizeTierStillReferenced(string $name): self
    {
        return new self("\"{$name}\" cannot be deleted forever: product variants still use it, also removed variants and products in the Trash. Delete those products forever first, or keep the size in the Trash.");
    }
}
