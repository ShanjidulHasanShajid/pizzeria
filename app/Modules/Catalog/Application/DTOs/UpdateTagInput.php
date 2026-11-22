<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

use App\Modules\Catalog\Domain\Enums\TagType;

final readonly class UpdateTagInput
{
    /**
     * @param  ?string  $slug  null or empty: keep the current slug
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $slug,
        public TagType $type,
        public ?string $icon,
        public bool $isActive,
    ) {}
}
