<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

use App\Modules\Content\Domain\Enums\ContentType;
use DateTimeImmutable;

/**
 * One deleted content item or location, for the Trash screen.
 */
final readonly class TrashedContentItem
{
    /**
     * @param  ?ContentType  $type  null is a location
     */
    public function __construct(
        public ?ContentType $type,
        public int $id,
        public string $name,
        public DateTimeImmutable $deletedAt,
    ) {}
}
