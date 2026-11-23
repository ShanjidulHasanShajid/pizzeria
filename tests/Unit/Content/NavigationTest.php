<?php

declare(strict_types=1);

use App\Modules\Content\Application\DTOs\NavItemInput;
use App\Modules\Content\Application\DTOs\NavItemView;
use App\Modules\Content\Application\DTOs\RenameNavItemInput;
use App\Modules\Content\Application\DTOs\ReorderNavItemsInput;
use App\Modules\Content\Application\UseCases\Navigation\RenameNavItem;
use App\Modules\Content\Application\UseCases\Navigation\ReorderNavItems;
use App\Modules\Content\Application\UseCases\Navigation\ToggleNavItemHighlight;
use App\Modules\Content\Application\UseCases\Navigation\ToggleNavItemVisibility;
use App\Modules\Content\Domain\Enums\NavRoute;
use App\Modules\Content\Infrastructure\Queries\RepositoryNavigationQuery;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use Tests\Unit\Content\Fakes\InMemoryNavItemRepository;
use Tests\Unit\Identity\PassThroughTransactionManager;

beforeEach(function (): void {
    $this->items = new InMemoryNavItemRepository;
    $this->reorder = fn (array $routes) => (new ReorderNavItems($this->items, new PassThroughTransactionManager))->handle(new ReorderNavItemsInput($routes));
    $this->labels = fn (): array => array_map(fn (NavItemView $item): string => $item->label, (new RepositoryNavigationQuery($this->items))->visible());
});

it('starts with the seven items of the plan and one highlight', function (): void {
    $highlighted = array_values(array_filter($this->items->all(), fn ($item): bool => $item->isHighlighted));

    expect(($this->labels)())->toBe(['Home', 'Menu', 'Customized Pizza', 'Gift Box', 'Our Story', 'Location', 'Contact Us'])
        ->and($this->items->all())->toHaveCount(count(NavRoute::cases()))
        ->and($highlighted)->toHaveCount(1)
        ->and($highlighted[0]->route)->toBe(NavRoute::Builder);
});

it('renames an item and refuses an empty or long label', function (): void {
    $rename = fn (string $label) => (new RenameNavItem($this->items))->handle(new RenameNavItemInput(NavRoute::Menu, $label));

    $rename(' Our Menu ');

    expect($this->items->get(NavRoute::Menu)->label)->toBe('Our Menu');
    expect(fn () => $rename('  '))->toThrow(ValidationFailed::class);
    expect(fn () => $rename(str_repeat('x', 31)))->toThrow(ValidationFailed::class);
});

it('shows, hides and highlights without changing anything else', function (): void {
    $faq = new NavItemInput(NavRoute::Faq);

    expect((new ToggleNavItemVisibility($this->items))->handle($faq))->toBeTrue()
        ->and((new ToggleNavItemHighlight($this->items))->handle($faq))->toBeTrue()
        ->and(($this->labels)())->toContain('FAQ')
        ->and($this->items->get(NavRoute::Faq)->isVisible)->toBeTrue()
        ->and((new ToggleNavItemVisibility($this->items))->handle($faq))->toBeFalse()
        ->and($this->items->get(NavRoute::Faq)->isHighlighted)->toBeTrue()
        ->and(($this->labels)())->not->toContain('FAQ');
});

it('saves a new order of all items', function (): void {
    ($this->reorder)(array_reverse(NavRoute::cases()));

    expect(($this->labels)())->toBe(['Contact Us', 'Location', 'Our Story', 'Gift Box', 'Customized Pizza', 'Menu', 'Home']);
});

it('refuses an order with a missing or a repeated item', function (): void {
    $routes = NavRoute::cases();

    expect(fn () => ($this->reorder)(array_slice($routes, 1)))->toThrow(ValidationFailed::class);
    expect(fn () => ($this->reorder)([$routes[1], ...array_slice($routes, 1)]))->toThrow(ValidationFailed::class);
});
