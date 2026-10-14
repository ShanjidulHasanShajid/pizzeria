<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Queries;

use App\Modules\Catalog\Application\DTOs\AdminCategoryRow;
use App\Modules\Catalog\Application\DTOs\AdminProductFilter;
use App\Modules\Catalog\Application\DTOs\AdminProductPage;
use App\Modules\Catalog\Application\DTOs\AdminProductRow;
use App\Modules\Catalog\Application\DTOs\TrashedCatalogItem;
use App\Modules\Catalog\Application\Queries\AdminCatalogQuery;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\KitchenStation as KitchenStationModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;
use App\Modules\Shared\Domain\ValueObjects\Money;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;

final class EloquentAdminCatalogQuery implements AdminCatalogQuery
{
    public function categoryTable(): array
    {
        $categories = CategoryModel::query()
            ->withCount('products')
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        // One extra query for all station names (deleted stations included), instead of one per row.
        $stationNames = KitchenStationModel::withTrashed()->pluck('name', 'id')->all();
        $byId = $categories->keyBy('id');
        $rows = [];

        foreach ($categories->whereNull('parent_id') as $topLevel) {
            $rows[] = $this->toCategoryRow($topLevel, null, $stationNames);

            foreach ($categories->where('parent_id', $topLevel->id) as $child) {
                $rows[] = $this->toCategoryRow($child, $topLevel->name, $stationNames);
            }
        }

        // A sub-category whose parent is in the Trash is listed at the end, so it is never hidden.
        foreach ($categories as $category) {
            if ($category->parent_id !== null && ! $byId->has($category->parent_id)) {
                $rows[] = $this->toCategoryRow($category, null, $stationNames);
            }
        }

        return $rows;
    }

    public function productTable(AdminProductFilter $filter): AdminProductPage
    {
        $query = ProductModel::query()
            ->with('mainImage')
            ->withCount('variants')
            ->withMin('variants', 'price')
            ->withMax('variants', 'price');

        if ($filter->search !== '') {
            $like = '%'.addcslashes($filter->search, '%_\\').'%';
            $query->where(function (Builder $inner) use ($like): void {
                $inner->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            });
        }

        if ($filter->categoryId !== null) {
            $query->where('category_id', $filter->categoryId);
        }

        if ($filter->type !== null) {
            $query->where('type', $filter->type->value);
        }

        if ($filter->tagId !== null) {
            $tagId = $filter->tagId;
            $query->whereHas('tags', fn (Builder $tags) => $tags->whereKey($tagId));
        }

        if ($filter->status === 'published') {
            $query->where('is_published', true);
        } elseif ($filter->status === 'unpublished') {
            $query->where('is_published', false);
        } elseif ($filter->status === 'sold_out') {
            $query->where('is_available', false);
        } elseif ($filter->status === 'trashed') {
            $query->onlyTrashed();
        }

        $page = $query->orderBy('sort')->orderBy('id')->paginate($filter->perPage, ['*'], 'page', $filter->page);
        $categoryNames = CategoryModel::withTrashed()->pluck('name', 'id')->all();

        return new AdminProductPage(
            items: array_values($page->getCollection()->map(fn (ProductModel $product): AdminProductRow => new AdminProductRow(
                id: $product->id,
                name: $product->name,
                slug: $product->slug,
                type: $product->type,
                categoryName: (string) ($categoryNames[$product->category_id] ?? ''),
                imagePath: $product->mainImage === null ? null : ($product->mainImage->thumbnail_path ?? $product->mainImage->path),
                lowestPrice: Money::fromPoisha((int) $product->variants_min_price),
                highestPrice: Money::fromPoisha((int) $product->variants_max_price),
                variantCount: (int) $product->variants_count,
                isPublished: $product->is_published,
                isAvailable: $product->is_available,
                isNew: $product->is_new,
                isHot: $product->is_hot,
                sort: $product->sort,
                isDeleted: $product->trashed(),
                updatedAt: $product->updated_at?->toDateTimeImmutable() ?? new DateTimeImmutable,
            ))->all()),
            total: $page->total(),
            page: $page->currentPage(),
            perPage: $page->perPage(),
        );
    }

    public function trashedItems(): array
    {
        $items = [
            ...$this->toTrashedItems('kitchen_station', KitchenStationModel::onlyTrashed()->get()),
            ...$this->toTrashedItems('size_tier', SizeTierModel::onlyTrashed()->get()),
            ...$this->toTrashedItems('tag', TagModel::onlyTrashed()->get()),
            ...$this->toTrashedItems('category', CategoryModel::onlyTrashed()->get()),
            ...$this->toTrashedItems('product', ProductModel::onlyTrashed()->get()),
        ];

        usort($items, fn (TrashedCatalogItem $a, TrashedCatalogItem $b): int => $b->deletedAt <=> $a->deletedAt);

        return $items;
    }

    /**
     * @param  array<int|string, mixed>  $stationNames  station id => name
     */
    private function toCategoryRow(CategoryModel $category, ?string $parentName, array $stationNames): AdminCategoryRow
    {
        return new AdminCategoryRow(
            id: $category->id,
            name: $category->name,
            slug: $category->slug,
            parentId: $category->parent_id,
            parentName: $parentName,
            kitchenStationName: (string) ($stationNames[$category->kitchen_station_id] ?? ''),
            productCount: (int) $category->products_count,
            sort: $category->sort,
            isActive: $category->is_active,
            isFeatured: $category->is_featured,
            showInMegaMenu: $category->show_in_mega_menu,
        );
    }

    /**
     * @param  'kitchen_station'|'size_tier'|'tag'|'category'|'product'  $kind
     * @param  iterable<KitchenStationModel|SizeTierModel|TagModel|CategoryModel|ProductModel>  $models
     * @return list<TrashedCatalogItem>
     */
    private function toTrashedItems(string $kind, iterable $models): array
    {
        $items = [];

        foreach ($models as $model) {
            $items[] = new TrashedCatalogItem(
                kind: $kind,
                id: $model->id,
                name: $model->name,
                deletedAt: $model->deleted_at?->toDateTimeImmutable() ?? new DateTimeImmutable,
            );
        }

        return $items;
    }
}
