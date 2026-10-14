<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

use App\Modules\Catalog\Domain\Enums\TagType;

/**
 * A tag as it is shown on a card or product page (icon plus name).
 */
final readonly class TagBadge
{
    public function __construct(
        public string $name,
        public string $slug,
        public TagType $type,
        public ?string $icon,
    ) {}
}
