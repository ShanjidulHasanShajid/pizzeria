<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

use DateTimeImmutable;

/**
 * One deleted option group or option, for the Trash screen.
 */
final readonly class TrashedOptionItem
{
    public function __construct(
        public int $id,
        public string $name,
        public DateTimeImmutable $deletedAt,
        public bool $isGroup,
    ) {}
}
