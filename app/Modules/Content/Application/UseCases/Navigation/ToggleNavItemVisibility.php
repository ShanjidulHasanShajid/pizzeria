<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Navigation;

use App\Modules\Content\Application\DTOs\NavItemInput;
use App\Modules\Content\Domain\Repositories\NavItemRepository;

/**
 * Shows or hides an item. Returns true when it is visible now.
 */
final class ToggleNavItemVisibility
{
    public function __construct(private readonly NavItemRepository $items) {}

    public function handle(NavItemInput $input): bool
    {
        $item = $this->items->get($input->route)->withVisibilityToggled();
        $this->items->save($item);

        return $item->isVisible;
    }
}
