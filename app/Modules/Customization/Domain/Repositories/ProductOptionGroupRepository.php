<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\Repositories;

use App\Modules\Customization\Domain\Entities\ProductOptionGroup;

/**
 * Which option groups are attached to which products, with the rules for each.
 */
interface ProductOptionGroupRepository
{
    public function find(int $id): ?ProductOptionGroup;

    /**
     * Everything attached to one product, in sort order.
     *
     * @return list<ProductOptionGroup>
     */
    public function forProduct(int $productId): array;

    /** Inserts the attachment when it has no id yet, otherwise updates its rules and position. Returns the id. */
    public function save(ProductOptionGroup $attachment): int;

    /** Detaches: the link is removed for good, the product and the group stay. */
    public function delete(int $id): void;

    /**
     * True when any product has this group, also a product in the Trash:
     * it must still find its group when it is restored.
     */
    public function isGroupAttached(int $optionGroupId): bool;
}
