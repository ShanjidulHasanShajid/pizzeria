<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Repositories;

use App\Modules\Catalog\Domain\Entities\Product;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Mappers\ProductMapper;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class EloquentProductRepository implements ProductRepository
{
    public function __construct(private readonly ProductMapper $mapper) {}

    public function find(int $id): ?Product
    {
        $model = $this->aggregateQuery()->find($id);

        return $model === null ? null : $this->mapper->toEntity($model);
    }

    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $models = $this->aggregateQuery()->whereKey($ids)->get();

        return array_values($models->map(fn (ProductModel $model): Product => $this->mapper->toEntity($model))->all());
    }

    /**
     * The whole aggregate in one transaction: the product row, then its variants,
     * images and tag links. Either everything is saved, or nothing.
     */
    public function save(Product $product): int
    {
        return DB::transaction(function () use ($product): int {
            $id = $product->id();
            $model = $id === null ? new ProductModel : ProductModel::query()->findOrFail($id);

            $model->fill($this->mapper->toAttributes($product));
            $model->save();

            $this->saveVariants($model, $product);
            $this->saveImages($model, $product);
            $model->tags()->sync($product->tagIds());

            return $model->id;
        });
    }

    public function delete(int $id): void
    {
        ProductModel::query()->findOrFail($id)->delete();
    }

    public function restore(int $id): void
    {
        ProductModel::onlyTrashed()->findOrFail($id)->restore();
    }

    public function slugExists(Slug $slug, ?int $ignoreId = null): bool
    {
        $query = ProductModel::withTrashed()->where('slug', $slug->value());

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    /**
     * Products that are not deleted, with everything the mapper needs, in 4 queries in total
     * (products, variants, images, tag links), however many products there are.
     *
     * @return Builder<ProductModel>
     */
    private function aggregateQuery(): Builder
    {
        return ProductModel::query()->with(['variants', 'images', 'tagsIncludingDeleted']);
    }

    /**
     * Inserts new variants, updates known ones, and soft-deletes the rows of variants
     * the aggregate no longer has (old cart lines and orders may still point to them).
     */
    private function saveVariants(ProductModel $model, Product $product): void
    {
        $keptIds = [];

        foreach ($product->variants() as $variant) {
            $attributes = $this->mapper->variantAttributes($variant);
            $variantId = $variant->id();

            if ($variantId === null) {
                $keptIds[] = $model->variants()->create($attributes)->id;

                continue;
            }

            $row = $model->variants()->findOrFail($variantId);
            $row->update($attributes);
            $keptIds[] = $row->id;
        }

        $model->variants()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Same idea for images, but removed image rows are really deleted (no Trash for images).
     */
    private function saveImages(ProductModel $model, Product $product): void
    {
        $keptIds = [];

        foreach ($product->images() as $image) {
            $attributes = $this->mapper->imageAttributes($image);
            $imageId = $image->id();

            if ($imageId === null) {
                $keptIds[] = $model->images()->create($attributes)->id;

                continue;
            }

            $row = $model->images()->findOrFail($imageId);
            $row->update($attributes);
            $keptIds[] = $row->id;
        }

        $model->images()->whereNotIn('id', $keptIds)->delete();
    }
}
