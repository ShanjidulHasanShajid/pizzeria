<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

final readonly class UpdateOptionGroupInput
{
    public function __construct(
        public int $id,
        public string $name,
        public string $displayTitle,
        public ?string $description,
        public bool $isActive,
    ) {}
}
