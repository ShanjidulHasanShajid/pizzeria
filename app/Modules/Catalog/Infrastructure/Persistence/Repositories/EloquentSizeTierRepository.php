<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Repositories;

use App\Modules\Catalog\Domain\Entities\SizeTier;
use App\Modules\Catalog\Domain\Repositories\SizeTierRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

final class EloquentSizeTierRepository implements SizeTierRepository
{
    public function find(int $id): ?SizeTier
    {
        $model = SizeTierModel::query()->find($id);

        return $model === null ? null : $this->toEntity($model);
    }

    public function save(SizeTier $sizeTier): int
    {
        $id = $sizeTier->id();
        $model = $id === null ? new SizeTierModel : SizeTierModel::query()->findOrFail($id);

        $model->fill([
            'name' => $sizeTier->name(),
            'slug' => $sizeTier->slug()->value(),
            'sort' => $sizeTier->sort()->value(),
            'is_active' => $sizeTier->isActive(),
        ]);
        $model->save();

        return $model->id;
    }

    public function delete(int $id): void
    {
        SizeTierModel::query()->findOrFail($id)->delete();
    }

    public function restore(int $id): void
    {
        SizeTierModel::onlyTrashed()->findOrFail($id)->restore();
    }

    public function slugExists(Slug $slug, ?int $ignoreId = null): bool
    {
        $query = SizeTierModel::withTrashed()->where('slug', $slug->value());

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    public function isInUse(int $id): bool
    {
        return ProductVariantModel::query()->where('size_tier_id', $id)->exists();
    }

    private function toEntity(SizeTierModel $model): SizeTier
    {
        return SizeTier::reconstitute(
            $model->id,
            $model->name,
            Slug::fromString($model->slug),
            SortOrder::fromInt($model->sort),
            $model->is_active,
        );
    }
}
