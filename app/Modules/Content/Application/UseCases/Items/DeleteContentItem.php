<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Items;

use App\Modules\Content\Application\DTOs\ContentItemInput;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Moves an item to the Trash. Its image files stay, so it can be restored.
 */
final class DeleteContentItem
{
    public function __construct(private readonly ContentItemRepository $items) {}

    public function handle(ContentItemInput $input): void
    {
        if ($this->items->find($input->type, $input->id) === null) {
            throw EntityNotFound::withId($input->type->singular(), $input->id);
        }

        $this->items->delete($input->type, $input->id);
    }
}
