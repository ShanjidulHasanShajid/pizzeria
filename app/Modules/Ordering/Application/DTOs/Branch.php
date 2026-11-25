<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\DTOs;

/**
 * A branch of the shop, as far as Ordering needs to know it.
 */
final readonly class Branch
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $isActive,
    ) {}
}
