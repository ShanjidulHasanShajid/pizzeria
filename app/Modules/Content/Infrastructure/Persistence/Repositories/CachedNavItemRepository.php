<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Repositories;

use App\Modules\Content\Domain\Enums\NavRoute;
use App\Modules\Content\Domain\Repositories\NavItemRepository;
use App\Modules\Content\Domain\ValueObjects\NavItem;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;

/**
 * The navbar stands on every page, so its items are read from the cache.
 */
final class CachedNavItemRepository implements NavItemRepository
{
    public const CACHE_KEY = 'content.navigation';

    public function __construct(
        private readonly NavItemRepository $inner,
        private readonly Cache $cache,
    ) {}

    public function all(): array
    {
        /** @var list<array{route: string, label: string, sort: int, visible: bool, highlighted: bool}> $rows */
        $rows = $this->cache->rememberForever(self::CACHE_KEY, fn (): array => array_map(
            fn (NavItem $item): array => [
                'route' => $item->route->value,
                'label' => $item->label,
                'sort' => $item->sort->value(),
                'visible' => $item->isVisible,
                'highlighted' => $item->isHighlighted,
            ],
            $this->inner->all(),
        ));

        $items = [];

        foreach ($rows as $row) {
            $route = NavRoute::tryFrom($row['route']); // a page removed from the whitelist may still be cached

            if ($route !== null) {
                $items[] = NavItem::reconstitute($route, $row['label'], SortOrder::fromInt($row['sort']), $row['visible'], $row['highlighted']);
            }
        }

        return $items;
    }

    public function get(NavRoute $route): NavItem
    {
        return $this->inner->get($route);
    }

    public function save(NavItem $item): void
    {
        $this->inner->save($item);

        DB::afterCommit(fn () => $this->cache->forget(self::CACHE_KEY));
    }
}
