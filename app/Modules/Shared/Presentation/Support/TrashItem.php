<?php

declare(strict_types=1);

namespace App\Modules\Shared\Presentation\Support;

use DateTimeImmutable;

/**
 * One deleted record on the Trash screen. $kind is the key of its TrashKind.
 */
final readonly class TrashItem
{
    public function __construct(
        public string $kind,
        public int $id,
        public string $name,
        public DateTimeImmutable $deletedAt,
    ) {}
}
