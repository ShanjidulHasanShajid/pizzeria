<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Controllers\Admin;

use App\Modules\Catalog\Application\DTOs\AdminCategoryFilter;
use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Application\DTOs\CreateCategoryInput;
use App\Modules\Catalog\Application\DTOs\ImageUpload;
use App\Modules\Catalog\Application\DTOs\ReorderInput;
use App\Modules\Catalog\Application\DTOs\UpdateCategoryInput;
use App\Modules\Catalog\Application\Queries\AdminCatalogQuery;
use App\Modules\Catalog\Application\UseCases\Categories\CreateCategory;
use App\Modules\Catalog\Application\UseCases\Categories\DeleteCategory;
use App\Modules\Catalog\Application\UseCases\Categories\ForceDeleteCategory;
use App\Modules\Catalog\Application\UseCases\Categories\ReorderCategories;
use App\Modules\Catalog\Application\UseCases\Categories\RestoreCategory;
use App\Modules\Catalog\Application\UseCases\Categories\ToggleCategoryActive;
use App\Modules\Catalog\Application\UseCases\Categories\ToggleCategoryFeatured;
use App\Modules\Catalog\Application\UseCases\Categories\UpdateCategory;
use App\Modules\Catalog\Presentation\Http\Controllers\Admin\Concerns\RunsCatalogActions;
use App\Modules\Catalog\Presentation\Http\Requests\CategoryRequest;
use App\Modules\Catalog\Presentation\Http\Requests\ReorderRequest;
use App\Modules\Shared\Application\Ports\FileStorage;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;

/**
 * Admin screens for categories and sub-categories. Routes require "manage-catalog";
 * "delete forever" also requires "force-delete" (super admins).
 */
final class CategoryController
{
    use RunsCatalogActions;

    private const STATUSES = ['all', 'active', 'inactive', 'trashed'];

    public function index(Request $request, AdminCatalogQuery $query, FileStorage $files): View
    {
        $status = $request->string('status', 'all')->toString();

        $filter = new AdminCategoryFilter(
            search: $request->string('q')->trim()->toString(),
            status: in_array($status, self::STATUSES, true) ? $status : 'all',
        );

        return view('catalog::admin.categories.index', [
            'categories' => $query->categoryTable($filter),
            'filter' => $filter,
            'imageUrl' => $this->imageUrl($files),
        ]);
    }

    public function create(AdminCatalogQuery $query, FileStorage $files): View
    {
        return view('catalog::admin.categories.create', [
            'imageUrl' => $this->imageUrl($files),
            ...$this->formOptions($query),
        ]);
    }

    public function store(CategoryRequest $request, CreateCategory $createCategory): RedirectResponse
    {
        $this->runFormAction(fn () => $createCategory->handle(new CreateCategoryInput(
            name: $request->string('name')->toString(),
            slug: $request->input('slug'),
            parentId: $request->filled('parent_id') ? $request->integer('parent_id') : null,
            kitchenStationId: $request->integer('kitchen_station_id'),
            description: $request->input('description'),
            isActive: $request->boolean('is_active'),
            isFeatured: $request->boolean('is_featured'),
            showInMegaMenu: $request->boolean('show_in_mega_menu'),
            image: $this->upload($request),
        )));

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(int $category, AdminCatalogQuery $query, FileStorage $files): View
    {
        $detail = $query->category($category);

        if ($detail === null) {
            abort(404);
        }

        return view('catalog::admin.categories.edit', [
            'category' => $detail,
            'imageUrl' => $this->imageUrl($files),
            ...$this->formOptions($query, exceptId: $detail->id),
        ]);
    }

    public function update(int $category, CategoryRequest $request, UpdateCategory $updateCategory): RedirectResponse
    {
        $this->runFormAction(fn () => $updateCategory->handle(new UpdateCategoryInput(
            id: $category,
            name: $request->string('name')->toString(),
            slug: $request->input('slug'),
            parentId: $request->filled('parent_id') ? $request->integer('parent_id') : null,
            kitchenStationId: $request->integer('kitchen_station_id'),
            description: $request->input('description'),
            isActive: $request->boolean('is_active'),
            isFeatured: $request->boolean('is_featured'),
            showInMegaMenu: $request->boolean('show_in_mega_menu'),
            newImage: $this->upload($request),
            removeImage: $request->boolean('remove_image'),
        )));

        return redirect()->route('admin.categories.index')->with('success', 'Category saved.');
    }

    public function toggleActive(int $category, ToggleCategoryActive $toggleActive): RedirectResponse
    {
        return $this->runListAction(fn () => $toggleActive->handle(new CatalogItemInput($category)), 'Category switched.');
    }

    public function toggleFeatured(int $category, ToggleCategoryFeatured $toggleFeatured): RedirectResponse
    {
        return $this->runListAction(fn () => $toggleFeatured->handle(new CatalogItemInput($category)), 'Featured changed.');
    }

    public function destroy(int $category, DeleteCategory $deleteCategory): RedirectResponse
    {
        return $this->runListAction(fn () => $deleteCategory->handle(new CatalogItemInput($category)), 'Category moved to the trash.');
    }

    public function restore(int $category, RestoreCategory $restoreCategory): RedirectResponse
    {
        return $this->runListAction(fn () => $restoreCategory->handle(new CatalogItemInput($category)), 'Category restored.');
    }

    public function forceDestroy(int $category, ForceDeleteCategory $forceDelete): RedirectResponse
    {
        return $this->runListAction(fn () => $forceDelete->handle(new CatalogItemInput($category)), 'Category deleted forever.');
    }

    public function reorder(ReorderRequest $request, ReorderCategories $reorderCategories): Response
    {
        $this->runFormAction(fn () => $reorderCategories->handle(new ReorderInput($request->ids())));

        return response()->noContent();
    }

    private function upload(Request $request): ?ImageUpload
    {
        $file = $request->file('image');

        return $file instanceof UploadedFile ? new ImageUpload((string) $file->get()) : null;
    }

    /**
     * The views get a small function that turns a stored path into a browser address,
     * through the FileStorage port (so Blade never needs to know which disk is used).
     *
     * @return Closure(?string): ?string
     */
    private function imageUrl(FileStorage $files): Closure
    {
        return fn (?string $path): ?string => $path === null ? null : $files->url($path);
    }

    /**
     * @return array{parents: array<int, string>, stations: array<int, string>}
     */
    private function formOptions(AdminCatalogQuery $query, ?int $exceptId = null): array
    {
        $parents = $query->topLevelCategoryOptions();
        unset($parents[$exceptId ?? 0]); // a category cannot be its own parent

        $stations = [];

        foreach ($query->kitchenStationTable() as $station) {
            $stations[$station->id] = $station->name.($station->isActive ? '' : ' (inactive)');
        }

        return ['parents' => $parents, 'stations' => $stations];
    }
}
