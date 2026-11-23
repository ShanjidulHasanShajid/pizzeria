<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

use App\Modules\Content\Domain\Enums\ContentType;

/**
 * Input for actions that need only one item: delete, restore, toggle.
 */
final readonly class ContentItemInput
{
    public function __construct(
        public ContentType $type,
        public int $id,
    ) {}
}
