<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Persistence\Repositories;

use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Customization\Infrastructure\Persistence\Mappers\ProductOptionGroupMapper;
use App\Modules\Customization\Infrastructure\Persistence\Models\ProductOptionGroup as ProductOptionGroupModel;

final class EloquentProductOptionGroupRepository implements ProductOptionGroupRepository
{
    public function __construct(private readonly ProductOptionGroupMapper $mapper) {}

    public function find(int $id): ?ProductOptionGroup
    {
        $model = ProductOptionGroupModel::query()->find($id);

        return $model === null ? null : $this->mapper->toEntity($model);
    }

    public function forProduct(int $productId): array
    {
        $models = ProductOptionGroupModel::query()
            ->where('product_id', $productId)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return array_values($models->map(fn (ProductOptionGroupModel $model): ProductOptionGroup => $this->mapper->toEntity($model))->all());
    }

    public function save(ProductOptionGroup $attachment): int
    {
        $id = $attachment->id();
        $model = $id === null ? new ProductOptionGroupModel : ProductOptionGroupModel::query()->findOrFail($id);

        $model->fill($this->mapper->toAttributes($attachment));
        $model->save();

        return $model->id;
    }

    public function delete(int $id): void
    {
        ProductOptionGroupModel::query()->findOrFail($id)->delete();
    }

    public function isGroupAttached(int $optionGroupId): bool
    {
        return ProductOptionGroupModel::query()->where('option_group_id', $optionGroupId)->exists();
    }
}
