<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

use App\Modules\Catalog\Domain\Enums\TagType;

final readonly class CreateTagInput
{
    /**
     * @param  ?string  $slug  null or empty: made from the name
     * @param  ?string  $icon  an icon name from the site's icon set, or null
     */
    public function __construct(
        public string $name,
        public ?string $slug,
        public TagType $type,
        public ?string $icon,
    ) {}
}
