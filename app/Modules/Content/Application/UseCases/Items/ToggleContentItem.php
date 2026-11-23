<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Items;

use App\Modules\Content\Application\DTOs\ContentItemInput;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Switches an item on or off. Returns true when it is on now.
 */
final class ToggleContentItem
{
    public function __construct(private readonly ContentItemRepository $items) {}

    public function handle(ContentItemInput $input): bool
    {
        $item = $this->items->find($input->type, $input->id)
            ?? throw EntityNotFound::withId($input->type->singular(), $input->id);

        $toggled = $item->toggled();
        $this->items->save($toggled);

        return $toggled->isActive;
    }
}
