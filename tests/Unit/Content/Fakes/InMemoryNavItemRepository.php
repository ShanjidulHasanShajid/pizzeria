<?php

declare(strict_types=1);

namespace Tests\Unit\Content\Fakes;

use App\Modules\Content\Domain\Enums\NavRoute;
use App\Modules\Content\Domain\Repositories\NavItemRepository;
use App\Modules\Content\Domain\ValueObjects\NavItem;

/**
 * A NavItemRepository in an array, for use case tests.
 */
final class InMemoryNavItemRepository implements NavItemRepository
{
    /** @var array<string, NavItem> */
    private array $saved = [];

    public function all(): array
    {
        $items = array_map($this->get(...), NavRoute::cases());
        usort($items, fn (NavItem $a, NavItem $b): int => $a->sort->value() <=> $b->sort->value());

        return $items;
    }

    public function get(NavRoute $route): NavItem
    {
        return $this->saved[$route->value] ?? NavItem::default($route);
    }

    public function save(NavItem $item): void
    {
        $this->saved[$item->route->value] = $item;
    }
}
