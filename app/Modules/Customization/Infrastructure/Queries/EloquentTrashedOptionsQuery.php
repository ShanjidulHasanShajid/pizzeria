<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Queries;

use App\Modules\Customization\Application\DTOs\TrashedOptionItem;
use App\Modules\Customization\Application\Queries\TrashedOptionsQuery;
use App\Modules\Customization\Infrastructure\Persistence\Models\Option as OptionModel;
use App\Modules\Customization\Infrastructure\Persistence\Models\OptionGroup as OptionGroupModel;
use DateTimeImmutable;

final class EloquentTrashedOptionsQuery implements TrashedOptionsQuery
{
    public function items(): array
    {
        $items = [];

        foreach (OptionGroupModel::onlyTrashed()->get() as $group) {
            $items[] = new TrashedOptionItem(
                id: $group->id,
                name: $group->name,
                deletedAt: $group->deleted_at?->toDateTimeImmutable() ?? new DateTimeImmutable,
                isGroup: true,
            );
        }

        // One extra query for all group names (deleted groups included), instead of one per option.
        $groupNames = OptionGroupModel::withTrashed()->pluck('name', 'id')->all();

        foreach (OptionModel::onlyTrashed()->get() as $option) {
            $items[] = new TrashedOptionItem(
                id: $option->id,
                name: $option->name.' ('.($groupNames[$option->option_group_id] ?? '').')',
                deletedAt: $option->deleted_at?->toDateTimeImmutable() ?? new DateTimeImmutable,
                isGroup: false,
            );
        }

        return $items;
    }
}
