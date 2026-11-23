<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;

/**
 * A "category with three products" row. Without picked products the shop shows the first ones.
 */
final readonly class HomeRow
{
    public const MAX_PRODUCTS = 3;

    /**
     * @param  list<int>  $productIds
     */
    public function __construct(
        public int $categoryId,
        public array $productIds,
    ) {
        if (count($productIds) > self::MAX_PRODUCTS || count(array_unique($productIds)) !== count($productIds)) {
            throw InvalidValue::because('Choose at most three different products for a row.');
        }
    }
}
