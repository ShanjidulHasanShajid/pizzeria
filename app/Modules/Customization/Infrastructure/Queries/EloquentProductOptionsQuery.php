<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Queries;

use App\Modules\Customization\Application\DTOs\OptionView;
use App\Modules\Customization\Application\DTOs\ProductOptionGroupView;
use App\Modules\Customization\Application\Queries\ProductOptionsQuery;
use App\Modules\Customization\Domain\Enums\PriceMode;
use App\Modules\Customization\Infrastructure\Persistence\Models\Option as OptionModel;
use App\Modules\Customization\Infrastructure\Persistence\Models\ProductOptionGroup as ProductOptionGroupModel;
use App\Modules\Shared\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Builder;

final class EloquentProductOptionsQuery implements ProductOptionsQuery
{
    public function forProduct(int $productId): array
    {
        // 4 queries in total (attachments, groups, options, prices), however many groups there are.
        $attachments = ProductOptionGroupModel::query()
            ->where('product_id', $productId)
            ->whereHas('optionGroup', fn (Builder $group) => $group->where('is_active', true))
            ->with('optionGroup.options.prices')
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return array_values($attachments->map(fn (ProductOptionGroupModel $attachment): ProductOptionGroupView => $this->toView($attachment))->all());
    }

    public function productIdsWithOptions(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        return array_values(ProductOptionGroupModel::query()
            ->whereIn('product_id', $productIds)
            ->whereHas('optionGroup', fn (Builder $group) => $group->where('is_active', true))
            ->distinct()
            ->orderBy('product_id')
            ->pluck('product_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all());
    }

    private function toView(ProductOptionGroupModel $attachment): ProductOptionGroupView
    {
        $group = $attachment->optionGroup;

        return new ProductOptionGroupView(
            optionGroupId: $group->id,
            title: $group->display_title,
            description: $group->description,
            isRequired: $attachment->is_required,
            minimum: $attachment->min_select,
            maximum: $attachment->max_select,
            freeCount: $attachment->free_count,
            options: array_values($group->options
                ->sortBy([['sort', 'asc'], ['id', 'asc']])
                ->map(fn (OptionModel $option): OptionView => $this->toOptionView($option))
                ->all()),
        );
    }

    private function toOptionView(OptionModel $option): OptionView
    {
        $isFlat = $option->price_mode === PriceMode::Flat;
        $sizePrices = [];

        if (! $isFlat) {
            foreach ($option->prices->sortBy('size_tier_id') as $price) {
                $sizePrices[$price->size_tier_id] = Money::fromPoisha($price->price);
            }
        }

        return new OptionView(
            id: $option->id,
            name: $option->name,
            imagePath: $option->image_path,
            isVeg: $option->is_veg,
            isAvailable: $option->is_available,
            flatPrice: $isFlat && $option->flat_price !== null ? Money::fromPoisha($option->flat_price) : null,
            sizePrices: $sizePrices,
        );
    }
}
