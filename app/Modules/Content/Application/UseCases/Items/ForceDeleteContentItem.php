<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Items;

use App\Modules\Content\Application\DTOs\ContentItemInput;
use App\Modules\Content\Application\Services\ContentImages;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Deletes an item of the Trash for good: first the row, then its image files.
 */
final class ForceDeleteContentItem
{
    public function __construct(
        private readonly ContentItemRepository $items,
        private readonly ContentImages $images,
    ) {}

    public function handle(ContentItemInput $input): void
    {
        $item = $this->items->findDeleted($input->type, $input->id)
            ?? throw EntityNotFound::withId($input->type->singular(), $input->id);

        $this->items->forceDelete($input->type, $input->id);
        $this->images->deleteAll($item->imagePaths());
    }
}
