<?php

declare(strict_types=1);

namespace Tests\Unit\Customization\Fakes;

use App\Modules\Customization\Application\Ports\CustomizableProducts;

/**
 * Category id => product ids, instead of the Catalog module.
 */
final class InMemoryCustomizableProducts implements CustomizableProducts
{
    /**
     * @param  array<int, list<int>>  $byCategory
     */
    public function __construct(private readonly array $byCategory) {}

    public function exists(int $productId): bool
    {
        return in_array($productId, array_merge(...array_values($this->byCategory)), true);
    }

    public function idsInCategory(int $categoryId): ?array
    {
        return $this->byCategory[$categoryId] ?? null;
    }
}
