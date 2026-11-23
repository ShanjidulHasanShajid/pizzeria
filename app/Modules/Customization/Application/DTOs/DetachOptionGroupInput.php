<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

final readonly class DetachOptionGroupInput
{
    public function __construct(
        public int $productId,
        public int $attachmentId,
    ) {}
}
