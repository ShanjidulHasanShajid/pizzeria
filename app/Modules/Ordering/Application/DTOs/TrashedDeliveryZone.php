<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\DTOs;

use DateTimeImmutable;

/**
 * One deleted delivery zone on the Trash screen.
 */
final readonly class TrashedDeliveryZone
{
    public function __construct(
        public int $id,
        public string $name,
        public DateTimeImmutable $deletedAt,
    ) {}
}
