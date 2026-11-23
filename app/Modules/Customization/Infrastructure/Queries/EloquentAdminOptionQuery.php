<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Queries;

use App\Modules\Customization\Application\DTOs\AdminOptionGroupDetail;
use App\Modules\Customization\Application\DTOs\AdminOptionRow;
use App\Modules\Customization\Application\DTOs\AttachedGroupRow;
use App\Modules\Customization\Application\DTOs\OptionGroupRow;
use App\Modules\Customization\Application\Queries\AdminOptionQuery;
use App\Modules\Customization\Domain\Enums\PriceMode;
use App\Modules\Customization\Infrastructure\Persistence\Models\Option as OptionModel;
use App\Modules\Customization\Infrastructure\Persistence\Models\OptionGroup as OptionGroupModel;
use App\Modules\Customization\Infrastructure\Persistence\Models\ProductOptionGroup as ProductOptionGroupModel;
use App\Modules\Shared\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

final class EloquentAdminOptionQuery implements AdminOptionQuery
{
    public function groupTable(): array
    {
        $groups = OptionGroupModel::query()
            ->withCount([
                'options',
                'options as sold_out_count' => fn (Builder $options) => $options->where('is_available', false),
                'attachments',
            ])
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return array_values($groups->map(fn (OptionGroupModel $group): OptionGroupRow => new OptionGroupRow(
            id: $group->id,
            name: $group->name,
            displayTitle: $group->display_title,
            isActive: $group->is_active,
            optionCount: (int) $group->options_count,
            soldOutCount: (int) $group->sold_out_count,
            productCount: (int) $group->attachments_count,
        ))->all());
    }

    public function group(int $id): ?AdminOptionGroupDetail
    {
        $group = OptionGroupModel::query()->with('options.prices')->find($id);

        return $group === null ? null : $this->toDetail($group);
    }

    public function groups(): array
    {
        $groups = OptionGroupModel::query()->with('options.prices')->orderBy('sort')->orderBy('id')->get();

        return array_values($groups->map(fn (OptionGroupModel $group): AdminOptionGroupDetail => $this->toDetail($group))->all());
    }

    public function attachedGroups(int $productId): array
    {
        $attachments = ProductOptionGroupModel::query()
            ->where('product_id', $productId)
            ->whereHas('optionGroup')
            ->with(['optionGroup' => fn (Relation $group) => $group->withCount('options')])
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return array_values($attachments->map(fn (ProductOptionGroupModel $attachment): AttachedGroupRow => new AttachedGroupRow(
            attachmentId: $attachment->id,
            optionGroupId: $attachment->option_group_id,
            groupName: $attachment->optionGroup->name,
            isGroupActive: $attachment->optionGroup->is_active,
            optionCount: (int) $attachment->optionGroup->options_count,
            isRequired: $attachment->is_required,
            minimum: $attachment->min_select,
            maximum: $attachment->max_select,
            freeCount: $attachment->free_count,
        ))->all());
    }

    private function toDetail(OptionGroupModel $group): AdminOptionGroupDetail
    {
        return new AdminOptionGroupDetail(
            id: $group->id,
            name: $group->name,
            displayTitle: $group->display_title,
            description: $group->description,
            isActive: $group->is_active,
            options: array_values($group->options
                ->sortBy([['sort', 'asc'], ['id', 'asc']])
                ->map(fn (OptionModel $option): AdminOptionRow => $this->toRow($option))
                ->all()),
        );
    }

    private function toRow(OptionModel $option): AdminOptionRow
    {
        $sizePrices = [];

        foreach ($option->prices->sortBy('size_tier_id') as $price) {
            $sizePrices[$price->size_tier_id] = Money::fromPoisha($price->price)->toTakaString();
        }

        return new AdminOptionRow(
            id: $option->id,
            name: $option->name,
            imagePath: $option->image_path,
            isVeg: $option->is_veg,
            isAvailable: $option->is_available,
            isPricedPerSize: $option->price_mode === PriceMode::PerSize,
            flatPriceTaka: $option->flat_price === null ? null : Money::fromPoisha($option->flat_price)->toTakaString(),
            sizePricesTaka: $sizePrices,
        );
    }
}
