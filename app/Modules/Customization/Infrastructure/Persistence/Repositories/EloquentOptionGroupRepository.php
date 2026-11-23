<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Persistence\Repositories;

use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Infrastructure\Persistence\Mappers\OptionGroupMapper;
use App\Modules\Customization\Infrastructure\Persistence\Models\Option as OptionModel;
use App\Modules\Customization\Infrastructure\Persistence\Models\OptionGroup as OptionGroupModel;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Support\Facades\DB;

final class EloquentOptionGroupRepository implements OptionGroupRepository
{
    public function __construct(private readonly OptionGroupMapper $mapper) {}

    public function find(int $id): ?OptionGroup
    {
        $model = OptionGroupModel::query()->with('options.prices')->find($id);

        return $model === null ? null : $this->mapper->toEntity($model);
    }

    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $models = OptionGroupModel::query()
            ->with('options.prices')
            ->whereKey($ids)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return array_values($models->map(fn (OptionGroupModel $model): OptionGroup => $this->mapper->toEntity($model))->all());
    }

    /**
     * The whole aggregate in one transaction: the group row, its options and their prices.
     */
    public function save(OptionGroup $group): int
    {
        return DB::transaction(function () use ($group): int {
            $id = $group->id();
            $model = $id === null ? new OptionGroupModel : OptionGroupModel::query()->findOrFail($id);

            $model->fill($this->mapper->toAttributes($group));
            $model->save();

            $this->saveOptions($model, $group);

            return $model->id;
        });
    }

    public function delete(int $id): void
    {
        OptionGroupModel::query()->findOrFail($id)->delete();
    }

    public function restore(int $id): void
    {
        OptionGroupModel::onlyTrashed()->findOrFail($id)->restore();
    }

    public function nextSortOrder(): SortOrder
    {
        // max() skips rows in the Trash (SoftDeletes) and gives null for an empty table.
        $highest = OptionGroupModel::query()->max('sort');

        return $highest === null ? SortOrder::first() : SortOrder::fromInt((int) $highest)->next();
    }

    public function findTrashed(int $id): ?OptionGroup
    {
        $model = OptionGroupModel::onlyTrashed()->with('options.prices')->find($id);

        return $model === null ? null : $this->mapper->toEntity($model);
    }

    public function forceDelete(int $id): void
    {
        // options and option_prices have cascadeOnDelete: the database removes them too.
        OptionGroupModel::onlyTrashed()->findOrFail($id)->forceDelete();
    }

    public function imagePaths(int $id): array
    {
        return array_values(OptionModel::withTrashed()
            ->where('option_group_id', $id)
            ->whereNotNull('image_path')
            ->pluck('image_path')
            ->map(fn (mixed $path): string => (string) $path)
            ->all());
    }

    public function findTrashedOption(int $optionId): ?Option
    {
        $model = OptionModel::onlyTrashed()->with('prices')->find($optionId);

        return $model === null ? null : $this->mapper->toOption($model);
    }

    public function groupIdOfTrashedOption(int $optionId): ?int
    {
        return OptionModel::onlyTrashed()->find($optionId)?->option_group_id;
    }

    public function restoreOption(int $optionId): void
    {
        OptionModel::onlyTrashed()->findOrFail($optionId)->restore();
    }

    public function forceDeleteOption(int $optionId): void
    {
        OptionModel::onlyTrashed()->findOrFail($optionId)->forceDelete();
    }

    /**
     * Inserts new options, updates known ones, and soft-deletes the rows of options
     * the aggregate no longer has.
     */
    private function saveOptions(OptionGroupModel $model, OptionGroup $group): void
    {
        $keptIds = [];

        foreach ($group->options() as $option) {
            $attributes = $this->mapper->optionAttributes($option);
            $optionId = $option->id();

            if ($optionId === null) {
                $row = $model->options()->create($attributes);
            } else {
                $row = $model->options()->findOrFail($optionId);
                $row->update($attributes);
            }

            $this->savePrices($row, $option);
            $keptIds[] = $row->id;
        }

        $model->options()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * A size without a price has no row at all, so the rows of removed prices are really deleted.
     */
    private function savePrices(OptionModel $row, Option $option): void
    {
        $prices = $option->sizePrices();

        $row->prices()->whereNotIn('size_tier_id', array_keys($prices))->delete();

        foreach ($prices as $sizeTierId => $price) {
            $row->prices()->updateOrCreate(['size_tier_id' => $sizeTierId], ['price' => $price->poisha()]);
        }
    }
}
