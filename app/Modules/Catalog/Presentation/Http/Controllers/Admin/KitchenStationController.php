<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Controllers\Admin;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Application\DTOs\CreateKitchenStationInput;
use App\Modules\Catalog\Application\DTOs\ReorderInput;
use App\Modules\Catalog\Application\DTOs\UpdateKitchenStationInput;
use App\Modules\Catalog\Application\Queries\AdminCatalogQuery;
use App\Modules\Catalog\Application\UseCases\KitchenStations\CreateKitchenStation;
use App\Modules\Catalog\Application\UseCases\KitchenStations\DeleteKitchenStation;
use App\Modules\Catalog\Application\UseCases\KitchenStations\ReorderKitchenStations;
use App\Modules\Catalog\Application\UseCases\KitchenStations\RestoreKitchenStation;
use App\Modules\Catalog\Application\UseCases\KitchenStations\UpdateKitchenStation;
use App\Modules\Catalog\Presentation\Http\Controllers\Admin\Concerns\RunsCatalogActions;
use App\Modules\Catalog\Presentation\Http\Requests\KitchenStationRequest;
use App\Modules\Catalog\Presentation\Http\Requests\ReorderRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Admin screens for kitchen stations. Routes require the "manage-catalog" gate.
 * Each method: read the request, call one query or one use case, answer.
 */
final class KitchenStationController
{
    use RunsCatalogActions;

    public function index(AdminCatalogQuery $query): View
    {
        return view('catalog::admin.kitchen-stations.index', ['stations' => $query->kitchenStationTable()]);
    }

    public function create(): View
    {
        return view('catalog::admin.kitchen-stations.create');
    }

    public function store(KitchenStationRequest $request, CreateKitchenStation $createStation): RedirectResponse
    {
        $createStation->handle(new CreateKitchenStationInput($request->string('name')->toString()));

        return redirect()->route('admin.kitchen-stations.index')->with('success', 'Kitchen station created.');
    }

    public function edit(int $station, AdminCatalogQuery $query): View
    {
        $row = $query->kitchenStation($station);

        if ($row === null) {
            abort(404);
        }

        return view('catalog::admin.kitchen-stations.edit', ['station' => $row]);
    }

    public function update(int $station, KitchenStationRequest $request, UpdateKitchenStation $updateStation): RedirectResponse
    {
        $this->runFormAction(fn () => $updateStation->handle(new UpdateKitchenStationInput(
            id: $station,
            name: $request->string('name')->toString(),
            isActive: $request->boolean('is_active'),
        )));

        return redirect()->route('admin.kitchen-stations.index')->with('success', 'Kitchen station saved.');
    }

    public function destroy(int $station, DeleteKitchenStation $deleteStation): RedirectResponse
    {
        return $this->runListAction(
            fn () => $deleteStation->handle(new CatalogItemInput($station)),
            'Kitchen station moved to the trash.',
        );
    }

    public function restore(int $station, RestoreKitchenStation $restoreStation): RedirectResponse
    {
        return $this->runListAction(
            fn () => $restoreStation->handle(new CatalogItemInput($station)),
            'Kitchen station restored.',
        );
    }

    /**
     * Called by the drag-and-drop list in the browser (JavaScript, not a form), so it answers
     * "204 No Content" instead of redirecting.
     */
    public function reorder(ReorderRequest $request, ReorderKitchenStations $reorderStations): Response
    {
        $this->runFormAction(fn () => $reorderStations->handle(new ReorderInput($request->ids())));

        return response()->noContent();
    }
}
