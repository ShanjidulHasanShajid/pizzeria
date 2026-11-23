<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

final readonly class BulkAttachOptionGroupInput
{
    public function __construct(
        public int $categoryId,
        public int $optionGroupId,
        public SelectionRulesInput $rules,
    ) {}
}
