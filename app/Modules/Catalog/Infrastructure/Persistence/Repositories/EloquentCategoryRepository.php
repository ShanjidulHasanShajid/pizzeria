<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Repositories;

use App\Modules\Catalog\Domain\Entities\Category;
use App\Modules\Catalog\Domain\Repositories\CategoryRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Mappers\CategoryMapper;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

final class EloquentCategoryRepository implements CategoryRepository
{
    public function __construct(private readonly CategoryMapper $mapper) {}

    public function find(int $id): ?Category
    {
        $model = CategoryModel::query()->find($id);

        return $model === null ? null : $this->mapper->toEntity($model);
    }

    public function findTrashed(int $id): ?Category
    {
        $model = CategoryModel::onlyTrashed()->find($id);

        return $model === null ? null : $this->mapper->toEntity($model);
    }

    public function nextSortOrder(?int $parentId): SortOrder
    {
        // where('parent_id', null) would never match; whereNull() asks "IS NULL".
        $query = CategoryModel::query();

        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        $highest = $query->max('sort');

        return $highest === null ? SortOrder::first() : SortOrder::fromInt((int) $highest)->next();
    }

    public function isUsedIncludingTrash(int $id): bool
    {
        return ProductModel::withTrashed()->where('category_id', $id)->exists()
            || CategoryModel::withTrashed()->where('parent_id', $id)->exists();
    }

    public function forceDelete(int $id): void
    {
        CategoryModel::onlyTrashed()->findOrFail($id)->forceDelete();
    }

    public function save(Category $category): int
    {
        $id = $category->id();
        $model = $id === null ? new CategoryModel : CategoryModel::query()->findOrFail($id);

        $model->fill($this->mapper->toAttributes($category));
        $model->save();

        return $model->id;
    }

    public function delete(int $id): void
    {
        CategoryModel::query()->findOrFail($id)->delete();
    }

    public function restore(int $id): void
    {
        CategoryModel::onlyTrashed()->findOrFail($id)->restore();
    }

    public function slugExists(Slug $slug, ?int $ignoreId = null): bool
    {
        $query = CategoryModel::withTrashed()->where('slug', $slug->value());

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    public function hasProducts(int $id): bool
    {
        return ProductModel::query()->where('category_id', $id)->exists();
    }

    public function hasChildren(int $id): bool
    {
        return CategoryModel::query()->where('parent_id', $id)->exists();
    }
}
