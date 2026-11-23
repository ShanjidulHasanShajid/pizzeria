<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

final readonly class AttachOptionGroupInput
{
    public function __construct(
        public int $productId,
        public int $optionGroupId,
        public SelectionRulesInput $rules,
    ) {}
}
