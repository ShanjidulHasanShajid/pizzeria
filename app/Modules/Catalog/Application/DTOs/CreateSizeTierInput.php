<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

final readonly class CreateSizeTierInput
{
    /**
     * @param  ?string  $slug  null or empty: made from the name
     */
    public function __construct(
        public string $name,
        public ?string $slug,
    ) {}
}
