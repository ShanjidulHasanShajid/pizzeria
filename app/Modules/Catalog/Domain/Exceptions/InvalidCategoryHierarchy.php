<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\DomainException;

/**
 * Categories may have only one level of sub-categories (docs/business-rules.md section 3).
 */
final class InvalidCategoryHierarchy extends DomainException
{
    public static function parentIsSubCategory(string $parentName): self
    {
        return new self("\"{$parentName}\" is already a sub-category. Only one level of sub-categories is allowed.");
    }

    public static function hasSubCategories(string $categoryName): self
    {
        return new self("\"{$categoryName}\" has sub-categories, so it cannot become a sub-category itself.");
    }

    public static function cannotBeItsOwnParent(): self
    {
        return new self('A category cannot be its own parent.');
    }

    public static function parentNotSaved(): self
    {
        return new self('Save the parent category before adding sub-categories to it.');
    }
}
