<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Presentation\Http\Controllers\Admin;

use App\Modules\Ordering\Application\DTOs\DeliveryZoneIdInput;
use App\Modules\Ordering\Application\DTOs\ReorderDeliveryZonesInput;
use App\Modules\Ordering\Application\Ports\Branches;
use App\Modules\Ordering\Application\Queries\AdminDeliveryZoneQuery;
use App\Modules\Ordering\Application\UseCases\DeliveryZones\CreateDeliveryZone;
use App\Modules\Ordering\Application\UseCases\DeliveryZones\DeleteDeliveryZone;
use App\Modules\Ordering\Application\UseCases\DeliveryZones\ForceDeleteDeliveryZone;
use App\Modules\Ordering\Application\UseCases\DeliveryZones\ReorderDeliveryZones;
use App\Modules\Ordering\Application\UseCases\DeliveryZones\RestoreDeliveryZone;
use App\Modules\Ordering\Application\UseCases\DeliveryZones\ToggleDeliveryZone;
use App\Modules\Ordering\Application\UseCases\DeliveryZones\UpdateDeliveryZone;
use App\Modules\Ordering\Domain\Entities\DeliveryZone;
use App\Modules\Ordering\Presentation\Http\Requests\DeliveryZoneRequest;
use App\Modules\Ordering\Presentation\Http\Requests\ReorderRequest;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Presentation\Support\ValidationFailedTranslator;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Delivery zones. Routes require "manage-sales".
 */
final class DeliveryZoneController
{
    public function index(AdminDeliveryZoneQuery $query): View
    {
        return view('ordering::admin.delivery-zones.index', ['zones' => $query->table()]);
    }

    public function create(Branches $branches): View
    {
        return view('ordering::admin.delivery-zones.create', [
            'branches' => $branches->all(),
            'startingFee' => DeliveryZone::startingFee()->toTakaString(),
        ]);
    }

    public function store(DeliveryZoneRequest $request, CreateDeliveryZone $createZone): RedirectResponse
    {
        $this->runAction(fn () => $createZone->handle($request->toInput(null)));

        return redirect()->route('admin.delivery-zones.index')->with('success', 'Delivery zone created.');
    }

    public function edit(int $zone, AdminDeliveryZoneQuery $query, Branches $branches): View
    {
        $row = $query->zone($zone);

        if ($row === null) {
            abort(404);
        }

        return view('ordering::admin.delivery-zones.edit', ['zone' => $row, 'branches' => $branches->all()]);
    }

    public function update(int $zone, DeliveryZoneRequest $request, UpdateDeliveryZone $updateZone): RedirectResponse
    {
        $this->runAction(fn () => $updateZone->handle($request->toInput($zone)));

        return redirect()->route('admin.delivery-zones.index')->with('success', 'Delivery zone saved.');
    }

    public function toggle(int $zone, ToggleDeliveryZone $toggleZone): RedirectResponse
    {
        $isActive = $this->runAction(fn (): bool => $toggleZone->handle(new DeliveryZoneIdInput($zone)));

        return back()->with('success', $isActive ? 'Delivery zone switched on.' : 'Delivery zone switched off.');
    }

    public function destroy(int $zone, DeleteDeliveryZone $deleteZone): RedirectResponse
    {
        $this->runAction(fn () => $deleteZone->handle(new DeliveryZoneIdInput($zone)));

        return back()->with('success', 'Delivery zone moved to the trash.');
    }

    public function restore(int $zone, RestoreDeliveryZone $restoreZone): RedirectResponse
    {
        $this->runAction(fn () => $restoreZone->handle(new DeliveryZoneIdInput($zone)));

        return back()->with('success', 'Delivery zone restored.');
    }

    public function forceDestroy(int $zone, ForceDeleteDeliveryZone $forceDelete): RedirectResponse
    {
        $this->runAction(fn () => $forceDelete->handle(new DeliveryZoneIdInput($zone)));

        return back()->with('success', 'Delivery zone deleted forever.');
    }

    /**
     * Called by the drag-and-drop list in the browser (JavaScript, not a form), so it answers
     * "204 No Content" instead of redirecting.
     */
    public function reorder(ReorderRequest $request, ReorderDeliveryZones $reorderZones): Response
    {
        $this->runAction(fn () => $reorderZones->handle(new ReorderDeliveryZonesInput($request->ids())));

        return response()->noContent();
    }

    /**
     * A broken rule goes back to the form with the error under its field; an unknown id is a 404 page.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $action
     * @return TResult
     */
    private function runAction(Closure $action): mixed
    {
        try {
            return $action();
        } catch (ValidationFailed $failure) {
            throw (new ValidationFailedTranslator)->toValidationException($failure);
        } catch (EntityNotFound) {
            abort(404);
        }
    }
}
