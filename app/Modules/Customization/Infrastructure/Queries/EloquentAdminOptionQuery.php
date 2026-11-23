<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Queries;

use App\Modules\Customization\Application\DTOs\AdminOptionGroupDetail;
use App\Modules\Customization\Application\DTOs\AdminOptionRow;
use App\Modules\Customization\Application\DTOs\OptionGroupRow;
use App\Modules\Customization\Application\Queries\AdminOptionQuery;
use App\Modules\Customization\Domain\Enums\PriceMode;
use App\Modules\Customization\Infrastructure\Persistence\Models\Option as OptionModel;
use App\Modules\Customization\Infrastructure\Persistence\Models\OptionGroup as OptionGroupModel;
use App\Modules\Shared\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Builder;

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

        if ($group === null) {
            return null;
        }

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
