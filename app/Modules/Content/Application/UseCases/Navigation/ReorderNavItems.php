<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Navigation;

use App\Modules\Content\Application\DTOs\ReorderNavItemsInput;
use App\Modules\Content\Domain\Enums\NavRoute;
use App\Modules\Content\Domain\Repositories\NavItemRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * Needs all items, because an item that was never saved has a position too.
 */
final class ReorderNavItems
{
    public function __construct(
        private readonly NavItemRepository $items,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(ReorderNavItemsInput $input): void
    {
        $given = array_map(fn (NavRoute $route): string => $route->value, $input->routes);
        $expected = array_map(fn (NavRoute $route): string => $route->value, NavRoute::cases());

        if (count($given) !== count($expected) || array_diff($expected, $given) !== []) {
            throw ValidationFailed::forField('ids', 'Send every item exactly once. Reload the page and try again.');
        }

        $this->transactions->run(function () use ($input): void {
            $sort = SortOrder::first();

            foreach ($input->routes as $route) {
                $this->items->save($this->items->get($route)->movedTo($sort));
                $sort = $sort->next();
            }
        });
    }
}
