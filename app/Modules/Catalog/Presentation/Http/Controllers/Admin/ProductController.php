<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Controllers\Admin;

use App\Modules\Catalog\Application\DTOs\AdminProductFilter;
use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Application\DTOs\UpdateProductInput;
use App\Modules\Catalog\Application\Queries\AdminCatalogQuery;
use App\Modules\Catalog\Application\UseCases\Products\CreateProduct;
use App\Modules\Catalog\Application\UseCases\Products\DeleteProduct;
use App\Modules\Catalog\Application\UseCases\Products\DuplicateProduct;
use App\Modules\Catalog\Application\UseCases\Products\ForceDeleteProduct;
use App\Modules\Catalog\Application\UseCases\Products\RestoreProduct;
use App\Modules\Catalog\Application\UseCases\Products\ToggleProductAvailability;
use App\Modules\Catalog\Application\UseCases\Products\ToggleProductPublished;
use App\Modules\Catalog\Application\UseCases\Products\UpdateProduct;
use App\Modules\Catalog\Domain\Enums\LeadTimeClass;
use App\Modules\Catalog\Domain\Enums\ProductType;
use App\Modules\Catalog\Presentation\Http\Controllers\Admin\Concerns\RunsCatalogActions;
use App\Modules\Catalog\Presentation\Http\Requests\ProductRequest;
use App\Modules\Shared\Application\Ports\FileStorage;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Admin screens for products: the list (7.23), create and edit (7.24 to 7.29), duplicate (7.30).
 * Images are managed by the Livewire component on the edit page (7.26).
 * Routes require "manage-catalog"; "delete forever" also requires "force-delete".
 */
final class ProductController
{
    use RunsCatalogActions;

    private const STATUSES = ['all', 'published', 'unpublished', 'sold_out', 'trashed'];

    public function index(Request $request, AdminCatalogQuery $query, FileStorage $files): View
    {
        $status = $request->string('status', 'all')->toString();

        $filter = new AdminProductFilter(
            search: $request->string('q')->trim()->toString(),
            categoryId: $request->filled('category') ? $request->integer('category') : null,
            type: ProductType::tryFrom($request->string('type')->toString()),
            tagId: $request->filled('tag') ? $request->integer('tag') : null,
            status: in_array($status, self::STATUSES, true) ? $status : 'all',
            page: max(1, $request->integer('page', 1)),
        );

        $page = $query->productTable($filter);

        return view('catalog::admin.products.index', [
            // A paginator keeps the filters in every page link ("pagination keeping filters", 7.23).
            'products' => new LengthAwarePaginator($page->items, $page->total, $page->perPage, $page->page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]),
            'filter' => $filter,
            'categories' => $this->categoryOptions($query),
            'tags' => $this->tagOptions($query),
            'types' => $this->typeOptions(),
            'imageUrl' => $this->imageUrl($files),
        ]);
    }

    public function create(AdminCatalogQuery $query): View
    {
        return view('catalog::admin.products.create', $this->formOptions($query));
    }

    public function store(ProductRequest $request, CreateProduct $createProduct): RedirectResponse
    {
        $id = $this->runFormAction(fn (): int => $createProduct->handle($request->toInput()));

        return redirect()->route('admin.products.edit', $id)->with('success', 'Product created. You can add images below.');
    }

    public function edit(int $product, AdminCatalogQuery $query): View
    {
        $detail = $query->product($product);

        if ($detail === null) {
            abort(404);
        }

        return view('catalog::admin.products.edit', ['product' => $detail, ...$this->formOptions($query)]);
    }

    public function update(int $product, ProductRequest $request, UpdateProduct $updateProduct): RedirectResponse
    {
        $this->runFormAction(fn () => $updateProduct->handle(new UpdateProductInput($product, $request->toInput())));

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product saved.');
    }

    public function togglePublished(int $product, ToggleProductPublished $togglePublished): RedirectResponse
    {
        return $this->runListAction(fn () => $togglePublished->handle(new CatalogItemInput($product)), 'Product published or hidden.');
    }

    public function toggleAvailability(int $product, ToggleProductAvailability $toggleAvailability): RedirectResponse
    {
        return $this->runListAction(fn () => $toggleAvailability->handle(new CatalogItemInput($product)), 'Product marked sold out or available.');
    }

    public function duplicate(int $product, DuplicateProduct $duplicateProduct): RedirectResponse
    {
        try {
            $copyId = $duplicateProduct->handle(new CatalogItemInput($product));
        } catch (EntityNotFound) {
            abort(404);
        }

        return redirect()->route('admin.products.edit', $copyId)
            ->with('success', 'Copy created. It is not published yet: check it, add images, then publish it.');
    }

    public function destroy(int $product, DeleteProduct $deleteProduct): RedirectResponse
    {
        return $this->runListAction(fn () => $deleteProduct->handle(new CatalogItemInput($product)), 'Product moved to the trash.');
    }

    public function restore(int $product, RestoreProduct $restoreProduct): RedirectResponse
    {
        return $this->runListAction(fn () => $restoreProduct->handle(new CatalogItemInput($product)), 'Product restored.');
    }

    public function forceDestroy(int $product, ForceDeleteProduct $forceDelete): RedirectResponse
    {
        return $this->runListAction(fn () => $forceDelete->handle(new CatalogItemInput($product)), 'Product deleted forever.');
    }

    /**
     * @return Closure(?string): ?string
     */
    private function imageUrl(FileStorage $files): Closure
    {
        return fn (?string $path): ?string => $path === null ? null : $files->url($path);
    }

    /**
     * @return array{categories: array<int, string>, sizeTiers: array<int, string>, tags: array<int, string>, leadTimeClasses: array<string, string>}
     */
    private function formOptions(AdminCatalogQuery $query): array
    {
        $sizeTiers = [];

        foreach ($query->sizeTierTable() as $sizeTier) {
            $sizeTiers[$sizeTier->id] = $sizeTier->name.($sizeTier->isActive ? '' : ' (inactive)');
        }

        $leadTimeClasses = [];

        foreach (LeadTimeClass::cases() as $class) {
            $leadTimeClasses[$class->value] = $class->label();
        }

        return [
            'categories' => $this->categoryOptions($query),
            'sizeTiers' => $sizeTiers,
            'tags' => $this->tagOptions($query),
            'leadTimeClasses' => $leadTimeClasses,
        ];
    }

    /**
     * Every category that is not deleted, sub-categories as "Drinks › Milkshakes".
     *
     * @return array<int, string>
     */
    private function categoryOptions(AdminCatalogQuery $query): array
    {
        $options = [];

        foreach ($query->categoryTable() as $row) {
            $options[$row->id] = $row->parentName === null ? $row->name : $row->parentName.' › '.$row->name;
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    private function tagOptions(AdminCatalogQuery $query): array
    {
        $options = [];

        foreach ($query->tagTable() as $tag) {
            $options[$tag->id] = $tag->name.($tag->isActive ? '' : ' (inactive)');
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private function typeOptions(): array
    {
        $options = [];

        foreach (ProductType::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }
}
