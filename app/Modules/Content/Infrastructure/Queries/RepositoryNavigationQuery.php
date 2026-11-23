<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Queries;

use App\Modules\Content\Application\DTOs\NavItemView;
use App\Modules\Content\Application\Queries\NavigationQuery;
use App\Modules\Content\Domain\Repositories\NavItemRepository;

final class RepositoryNavigationQuery implements NavigationQuery
{
    public function __construct(private readonly NavItemRepository $items) {}

    public function visible(): array
    {
        return array_values(array_filter($this->all(), fn (NavItemView $item): bool => $item->isVisible));
    }

    public function all(): array
    {
        return array_map(NavItemView::fromItem(...), $this->items->all());
    }
}
