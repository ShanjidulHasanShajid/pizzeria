<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\Ports;

/**
 * Which products may have option groups: not deleted, and not a combo (combos use slots).
 */
interface CustomizableProducts
{
    public function exists(int $productId): bool;

    /**
     * The products of a category and its sub-categories, hidden ones included.
     *
     * @return list<int>|null null when there is no such category
     */
    public function idsInCategory(int $categoryId): ?array;

    /**
     * @return array<int, string> category id => name; a sub-category reads "Drinks > Coffee"
     */
    public function categoryNames(): array;
}
