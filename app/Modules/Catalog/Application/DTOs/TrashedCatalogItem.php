<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

use DateTimeImmutable;

/**
 * One deleted catalog record on the Trash screen.
 */
final readonly class TrashedCatalogItem
{
    /**
     * @param  'kitchen_station'|'size_tier'|'tag'|'category'|'product'  $kind
     */
    public function __construct(
        public string $kind,
        public int $id,
        public string $name,
        public DateTimeImmutable $deletedAt,
    ) {}
}
