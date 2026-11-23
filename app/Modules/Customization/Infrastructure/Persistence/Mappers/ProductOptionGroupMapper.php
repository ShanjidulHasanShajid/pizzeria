<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Persistence\Mappers;

use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Customization\Infrastructure\Persistence\Models\ProductOptionGroup as ProductOptionGroupModel;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * Translates between a product_option_group row and the ProductOptionGroup entity.
 */
final class ProductOptionGroupMapper
{
    public function toEntity(ProductOptionGroupModel $model): ProductOptionGroup
    {
        return ProductOptionGroup::reconstitute(
            $model->id,
            $model->product_id,
            $model->option_group_id,
            SelectionRules::of($model->is_required, $model->min_select, $model->max_select, $model->free_count),
            SortOrder::fromInt($model->sort),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(ProductOptionGroup $attachment): array
    {
        $rules = $attachment->rules();

        return [
            'product_id' => $attachment->productId(),
            'option_group_id' => $attachment->optionGroupId(),
            'is_required' => $rules->isRequired(),
            'min_select' => $rules->minimum(),
            'max_select' => $rules->maximum(),
            'free_count' => $rules->freeCount(),
            'sort' => $attachment->sort()->value(),
        ];
    }
}
