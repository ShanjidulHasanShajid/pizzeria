<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

final readonly class UpdateSizeTierInput
{
    /**
     * @param  ?string  $slug  null or empty: keep the current slug
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $slug,
        public bool $isActive,
    ) {}
}
