<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

final readonly class UpdateAttachedRulesInput
{
    public function __construct(
        public int $productId,
        public int $attachmentId,
        public SelectionRulesInput $rules,
    ) {}
}
