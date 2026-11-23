<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\Ports;

/**
 * The categories and products that can be shown on the home page.
 */
interface HomeCatalog
{
    /**
     * @return array<int, string> category id => name; a sub-category reads "Drinks > Coffee"
     */
    public function categoryNames(): array;

    /**
     * Products of a category (and its sub-categories) that customers can see.
     *
     * @return array<int, string> product id => name
     */
    public function productNames(int $categoryId): array;
}
