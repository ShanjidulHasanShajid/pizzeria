<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Repositories;

use App\Modules\Content\Domain\Enums\NavRoute;
use App\Modules\Content\Domain\Repositories\NavItemRepository;
use App\Modules\Content\Domain\ValueObjects\NavItem;
use App\Modules\Content\Infrastructure\Persistence\Models\NavItem as NavItemModel;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * A row exists only for pages the admin has changed. A row with an unknown key is ignored.
 */
final class EloquentNavItemRepository implements NavItemRepository
{
    public function all(): array
    {
        $models = NavItemModel::query()->get()->keyBy('route_key');
        $items = [];

        foreach (NavRoute::cases() as $route) {
            $items[] = $this->toItem($route, $models->get($route->value));
        }

        usort($items, fn (NavItem $a, NavItem $b): int => $a->sort->value() <=> $b->sort->value());

        return $items;
    }

    public function get(NavRoute $route): NavItem
    {
        return $this->toItem($route, NavItemModel::query()->where('route_key', $route->value)->first());
    }

    public function save(NavItem $item): void
    {
        NavItemModel::query()->updateOrCreate(
            ['route_key' => $item->route->value],
            [
                'label' => $item->label,
                'sort' => $item->sort->value(),
                'is_visible' => $item->isVisible,
                'is_highlighted' => $item->isHighlighted,
            ],
        );
    }

    private function toItem(NavRoute $route, ?NavItemModel $model): NavItem
    {
        return $model === null
            ? NavItem::default($route)
            : NavItem::reconstitute($route, $model->label, SortOrder::fromInt($model->sort), $model->is_visible, $model->is_highlighted);
    }
}
