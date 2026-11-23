<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

final readonly class CreateOptionGroupInput
{
    public function __construct(
        public string $name,
        public string $displayTitle,
        public ?string $description,
    ) {}
}
