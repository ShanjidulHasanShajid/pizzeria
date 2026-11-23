<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Persistence\Mappers;

use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Infrastructure\Persistence\Models\Option as OptionModel;
use App\Modules\Customization\Infrastructure\Persistence\Models\OptionGroup as OptionGroupModel;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * Translates between the option_groups, options and option_prices rows and the OptionGroup aggregate.
 */
final class OptionGroupMapper
{
    /**
     * The model must come with options.prices loaded.
     */
    public function toEntity(OptionGroupModel $model): OptionGroup
    {
        return OptionGroup::reconstitute(
            id: $model->id,
            name: $model->name,
            displayTitle: $model->display_title,
            description: $model->description,
            isActive: $model->is_active,
            sort: SortOrder::fromInt($model->sort),
            options: array_values($model->options->map(fn (OptionModel $option): Option => $this->toOption($option))->all()),
        );
    }

    /**
     * The option_groups columns for fill().
     *
     * @return array<string, mixed>
     */
    public function toAttributes(OptionGroup $group): array
    {
        return [
            'name' => $group->name(),
            'display_title' => $group->displayTitle(),
            'description' => $group->description(),
            'sort' => $group->sort()->value(),
            'is_active' => $group->isActive(),
        ];
    }

    /**
     * The options columns. The prices per size are saved separately.
     *
     * @return array<string, mixed>
     */
    public function optionAttributes(Option $option): array
    {
        return [
            'name' => $option->name(),
            'image_path' => $option->imagePath(),
            'is_veg' => $option->isVeg(),
            'is_available' => $option->isAvailable(),
            'price_mode' => $option->priceMode(),
            'flat_price' => $option->flatPrice()?->poisha(),
            'sort' => $option->sort()->value(),
        ];
    }

    private function toOption(OptionModel $model): Option
    {
        $sizePrices = [];

        foreach ($model->prices as $price) {
            $sizePrices[$price->size_tier_id] = Money::fromPoisha($price->price);
        }

        return Option::reconstitute(
            $model->id,
            $model->name,
            $model->image_path,
            $model->is_veg,
            $model->is_available,
            SortOrder::fromInt($model->sort),
            $model->price_mode,
            $model->flat_price === null ? null : Money::fromPoisha($model->flat_price),
            $sizePrices,
        );
    }
}
