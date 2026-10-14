<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\DomainException;

/**
 * Only combos can be gift boxes (docs/business-rules.md section 6, decision 8).
 */
final class ProductNotGiftable extends DomainException
{
    public static function onlyCombos(string $productName): self
    {
        return new self("\"{$productName}\" is not a combo. Only combos can be gift boxes.");
    }
}
