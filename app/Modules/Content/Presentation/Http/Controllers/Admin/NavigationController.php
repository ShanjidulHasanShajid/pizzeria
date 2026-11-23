<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Http\Controllers\Admin;

use App\Modules\Content\Application\DTOs\NavItemInput;
use App\Modules\Content\Application\Queries\NavigationQuery;
use App\Modules\Content\Application\UseCases\Navigation\RenameNavItem;
use App\Modules\Content\Application\UseCases\Navigation\ReorderNavItems;
use App\Modules\Content\Application\UseCases\Navigation\ToggleNavItemHighlight;
use App\Modules\Content\Application\UseCases\Navigation\ToggleNavItemVisibility;
use App\Modules\Content\Domain\Enums\NavRoute;
use App\Modules\Content\Presentation\Http\Controllers\Admin\Concerns\RunsContentActions;
use App\Modules\Content\Presentation\Http\Requests\RenameNavItemRequest;
use App\Modules\Content\Presentation\Http\Requests\ReorderNavItemsRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * The navbar. Items are never added or deleted; {item} is a NavRoute, any other word is a 404.
 * Routes require "manage-content".
 */
final class NavigationController
{
    use RunsContentActions;

    public function index(NavigationQuery $query): View
    {
        return view('content::admin.navigation.index', ['items' => $query->all()]);
    }

    public function update(NavRoute $item, RenameNavItemRequest $request, RenameNavItem $rename): RedirectResponse
    {
        $this->runAction(fn () => $rename->handle($request->toInput($item)));

        return redirect()->route('admin.navigation.index')->with('success', 'Label saved.');
    }

    public function toggleVisibility(NavRoute $item, ToggleNavItemVisibility $toggle): RedirectResponse
    {
        $isVisible = $toggle->handle(new NavItemInput($item));

        return redirect()->route('admin.navigation.index')->with('success', $isVisible ? 'The item is shown now.' : 'The item is hidden now.');
    }

    public function toggleHighlight(NavRoute $item, ToggleNavItemHighlight $toggle): RedirectResponse
    {
        $isHighlighted = $toggle->handle(new NavItemInput($item));

        return redirect()->route('admin.navigation.index')->with('success', $isHighlighted ? 'The item is highlighted now.' : 'The item is not highlighted any more.');
    }

    public function reorder(ReorderNavItemsRequest $request, ReorderNavItems $reorder): Response
    {
        $this->runAction(fn () => $reorder->handle($request->toInput()));

        return response()->noContent();
    }
}
