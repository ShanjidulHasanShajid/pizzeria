<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

use App\Modules\Content\Domain\Enums\NavRoute;

final readonly class RenameNavItemInput
{
    public function __construct(
        public NavRoute $route,
        public string $label,
    ) {}
}
