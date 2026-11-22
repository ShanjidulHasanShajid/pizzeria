<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Controllers\Admin;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Application\DTOs\CreateSizeTierInput;
use App\Modules\Catalog\Application\DTOs\ReorderInput;
use App\Modules\Catalog\Application\DTOs\UpdateSizeTierInput;
use App\Modules\Catalog\Application\Queries\AdminCatalogQuery;
use App\Modules\Catalog\Application\UseCases\SizeTiers\CreateSizeTier;
use App\Modules\Catalog\Application\UseCases\SizeTiers\DeleteSizeTier;
use App\Modules\Catalog\Application\UseCases\SizeTiers\ReorderSizeTiers;
use App\Modules\Catalog\Application\UseCases\SizeTiers\RestoreSizeTier;
use App\Modules\Catalog\Application\UseCases\SizeTiers\ToggleSizeTierActive;
use App\Modules\Catalog\Application\UseCases\SizeTiers\UpdateSizeTier;
use App\Modules\Catalog\Presentation\Http\Controllers\Admin\Concerns\RunsCatalogActions;
use App\Modules\Catalog\Presentation\Http\Requests\ReorderRequest;
use App\Modules\Catalog\Presentation\Http\Requests\SizeTierRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Admin screens for size tiers (Personal, Medium, Large). Routes require "manage-catalog".
 */
final class SizeTierController
{
    use RunsCatalogActions;

    public function index(AdminCatalogQuery $query): View
    {
        return view('catalog::admin.size-tiers.index', ['sizeTiers' => $query->sizeTierTable()]);
    }

    public function create(): View
    {
        return view('catalog::admin.size-tiers.create');
    }

    public function store(SizeTierRequest $request, CreateSizeTier $createSizeTier): RedirectResponse
    {
        $this->runFormAction(fn () => $createSizeTier->handle(new CreateSizeTierInput(
            name: $request->string('name')->toString(),
            slug: $request->input('slug'),
        )));

        return redirect()->route('admin.size-tiers.index')->with('success', 'Size created.');
    }

    public function edit(int $sizeTier, AdminCatalogQuery $query): View
    {
        $row = $query->sizeTier($sizeTier);

        if ($row === null) {
            abort(404);
        }

        return view('catalog::admin.size-tiers.edit', ['sizeTier' => $row]);
    }

    public function update(int $sizeTier, SizeTierRequest $request, UpdateSizeTier $updateSizeTier): RedirectResponse
    {
        $this->runFormAction(fn () => $updateSizeTier->handle(new UpdateSizeTierInput(
            id: $sizeTier,
            name: $request->string('name')->toString(),
            slug: $request->input('slug'),
            isActive: $request->boolean('is_active'),
        )));

        return redirect()->route('admin.size-tiers.index')->with('success', 'Size saved.');
    }

    public function toggle(int $sizeTier, ToggleSizeTierActive $toggleSizeTier): RedirectResponse
    {
        return $this->runListAction(
            fn () => $toggleSizeTier->handle(new CatalogItemInput($sizeTier)),
            'Size switched.',
        );
    }

    public function destroy(int $sizeTier, DeleteSizeTier $deleteSizeTier): RedirectResponse
    {
        return $this->runListAction(
            fn () => $deleteSizeTier->handle(new CatalogItemInput($sizeTier)),
            'Size moved to the trash.',
        );
    }

    public function restore(int $sizeTier, RestoreSizeTier $restoreSizeTier): RedirectResponse
    {
        return $this->runListAction(
            fn () => $restoreSizeTier->handle(new CatalogItemInput($sizeTier)),
            'Size restored.',
        );
    }

    public function reorder(ReorderRequest $request, ReorderSizeTiers $reorderSizeTiers): Response
    {
        $this->runFormAction(fn () => $reorderSizeTiers->handle(new ReorderInput($request->ids())));

        return response()->noContent();
    }
}
