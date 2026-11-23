<?php

declare(strict_types=1);

use App\Modules\Content\Application\DTOs\NavItemView;
use App\Modules\Content\Application\Queries\NavigationQuery;
use App\Modules\Content\Domain\Enums\NavRoute;
use App\Modules\Content\Domain\Repositories\NavItemRepository;
use App\Modules\Content\Domain\ValueObjects\NavItem;
use App\Modules\Content\Infrastructure\Persistence\Models\NavItem as NavItemModel;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedNavItemRepository;
use App\Modules\Content\Presentation\Support\NavLinks;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Cache::forget(CachedNavItemRepository::CACHE_KEY);
    $this->items = app(NavItemRepository::class);
});

it('links only to pages that exist', function (NavRoute $route): void {
    expect(Route::has(NavLinks::routeName($route)))->toBeTrue()
        ->and(NavLinks::activePatterns($route))->not->toBe([])
        ->and(Route::getRoutes()->getByName(NavLinks::routeName($route))->parameterNames())->toBe([]);
})->with(NavRoute::cases());

it('has the defaults without any row, and ignores unknown rows', function (): void {
    NavItemModel::query()->create(['route_key' => 'gone', 'label' => 'Gone', 'sort' => 1, 'is_visible' => true, 'is_highlighted' => false]);

    expect($this->items->all())->toHaveCount(count(NavRoute::cases()))
        ->and($this->items->all()[0]->label)->toBe('Home');
});

it('saves an item in place and clears the cache', function (): void {
    expect($this->items->all()[1]->label)->toBe('Menu');

    $this->items->save($this->items->get(NavRoute::Menu)->rename('Our Menu')->movedTo(SortOrder::fromInt(5)));
    $this->items->save($this->items->get(NavRoute::Menu)->withVisibilityToggled()->withHighlightToggled());

    $first = $this->items->all()[0];

    expect($first)->toEqual(NavItem::reconstitute(NavRoute::Menu, 'Our Menu', SortOrder::fromInt(5), false, true))
        ->and($this->items->get(NavRoute::Menu))->toEqual($first);
    $this->assertDatabaseCount('nav_items', 1);
});

it('reads the database once and then the cache', function (): void {
    $query = app(NavigationQuery::class);
    $query->visible();

    DB::enableQueryLog();
    $labels = array_map(fn (NavItemView $item): string => $item->label, $query->visible());

    expect($labels)->toBe(['Home', 'Menu', 'Customized Pizza', 'Gift Box', 'Our Story', 'Location', 'Contact Us'])
        ->and(array_filter(DB::getQueryLog(), fn (array $entry): bool => str_contains($entry['query'], 'nav_items')))->toBe([]);
});
