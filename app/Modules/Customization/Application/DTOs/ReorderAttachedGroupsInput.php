<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

final readonly class ReorderAttachedGroupsInput
{
    /**
     * @param  list<int>  $attachmentIds  everything attached to the product, in the new order
     */
    public function __construct(
        public int $productId,
        public array $attachmentIds,
    ) {}
}
