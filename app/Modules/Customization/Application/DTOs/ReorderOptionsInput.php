<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

final readonly class ReorderOptionsInput
{
    /**
     * @param  list<int>  $optionIds  all options of the group in their new order
     */
    public function __construct(
        public int $optionGroupId,
        public array $optionIds,
    ) {}
}
