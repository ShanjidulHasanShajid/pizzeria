<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Navigation;

use App\Modules\Content\Application\DTOs\NavItemInput;
use App\Modules\Content\Domain\Repositories\NavItemRepository;

/**
 * Highlights an item or not. Returns true when it is highlighted now.
 */
final class ToggleNavItemHighlight
{
    public function __construct(private readonly NavItemRepository $items) {}

    public function handle(NavItemInput $input): bool
    {
        $item = $this->items->get($input->route)->withHighlightToggled();
        $this->items->save($item);

        return $item->isHighlighted;
    }
}
