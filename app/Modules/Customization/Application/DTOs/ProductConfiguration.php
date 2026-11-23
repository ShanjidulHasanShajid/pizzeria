<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

/**
 * Everything a customer can choose for one product: its sizes and its option groups.
 */
final readonly class ProductConfiguration
{
    /**
     * @param  list<ConfigurableVariant>  $variants  in sort order; sold-out ones included and marked
     * @param  list<ProductOptionGroupView>  $groups  in the order of the product
     */
    public function __construct(
        public int $productId,
        public array $variants,
        public array $groups,
    ) {}

    public function variant(int $id): ?ConfigurableVariant
    {
        foreach ($this->variants as $variant) {
            if ($variant->id === $id) {
                return $variant;
            }
        }

        return null;
    }
}
