<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

use App\Modules\Content\Domain\Enums\ContentType;

final readonly class ReorderContentItemsInput
{
    /**
     * @param  list<int>  $ids  in their new order
     */
    public function __construct(
        public ContentType $type,
        public array $ids,
    ) {}
}
