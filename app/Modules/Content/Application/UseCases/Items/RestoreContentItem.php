<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Items;

use App\Modules\Content\Application\DTOs\ContentItemInput;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;

/**
 * Brings an item back from the Trash. An id that is not in the Trash fails with "not found".
 */
final class RestoreContentItem
{
    public function __construct(private readonly ContentItemRepository $items) {}

    public function handle(ContentItemInput $input): void
    {
        $this->items->restore($input->type, $input->id);
    }
}
