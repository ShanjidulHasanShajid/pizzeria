<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Http\Controllers\Admin;

use App\Modules\Content\Application\DTOs\LocationIdInput;
use App\Modules\Content\Application\DTOs\ReorderLocationsInput;
use App\Modules\Content\Application\Queries\LocationQuery;
use App\Modules\Content\Application\UseCases\Locations\CreateLocation;
use App\Modules\Content\Application\UseCases\Locations\DeleteLocation;
use App\Modules\Content\Application\UseCases\Locations\ReorderLocations;
use App\Modules\Content\Application\UseCases\Locations\RestoreLocation;
use App\Modules\Content\Application\UseCases\Locations\ToggleLocation;
use App\Modules\Content\Application\UseCases\Locations\UpdateLocation;
use App\Modules\Content\Presentation\Http\Controllers\Admin\Concerns\RunsContentActions;
use App\Modules\Content\Presentation\Http\Requests\LocationRequest;
use App\Modules\Content\Presentation\Http\Requests\ReorderRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Branches of the shop. Routes require "manage-content".
 */
final class LocationController
{
    use RunsContentActions;

    public function index(LocationQuery $query): View
    {
        return view('content::admin.locations.index', ['locations' => $query->table()]);
    }

    public function create(): View
    {
        return view('content::admin.locations.create');
    }

    public function store(LocationRequest $request, CreateLocation $createLocation): RedirectResponse
    {
        $this->runAction(fn () => $createLocation->handle($request->toInput(null)));

        return redirect()->route('admin.locations.index')->with('success', 'Location created.');
    }

    public function edit(int $location, LocationQuery $query): View
    {
        $view = $query->location($location);

        if ($view === null) {
            abort(404);
        }

        return view('content::admin.locations.edit', ['location' => $view]);
    }

    public function update(int $location, LocationRequest $request, UpdateLocation $updateLocation): RedirectResponse
    {
        $this->runAction(fn () => $updateLocation->handle($request->toInput($location)));

        return redirect()->route('admin.locations.index')->with('success', 'Location saved.');
    }

    public function toggle(int $location, ToggleLocation $toggleLocation): RedirectResponse
    {
        $isActive = $this->runAction(fn (): bool => $toggleLocation->handle(new LocationIdInput($location)));

        return back()->with('success', $isActive ? 'Location switched on.' : 'Location switched off.');
    }

    public function destroy(int $location, DeleteLocation $deleteLocation): RedirectResponse
    {
        $this->runAction(fn () => $deleteLocation->handle(new LocationIdInput($location)));

        return back()->with('success', 'Location moved to the trash.');
    }

    public function restore(int $location, RestoreLocation $restoreLocation): RedirectResponse
    {
        $this->runAction(fn () => $restoreLocation->handle(new LocationIdInput($location)));

        return back()->with('success', 'Location restored.');
    }

    public function reorder(ReorderRequest $request, ReorderLocations $reorderLocations): Response
    {
        $this->runAction(fn () => $reorderLocations->handle(new ReorderLocationsInput($request->ids())));

        return response()->noContent();
    }
}
