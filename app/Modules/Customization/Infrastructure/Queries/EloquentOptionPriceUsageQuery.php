<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Queries;

use App\Modules\Customization\Application\Queries\OptionPriceUsageQuery;
use App\Modules\Customization\Domain\Enums\PriceMode;
use App\Modules\Customization\Infrastructure\Persistence\Models\OptionPrice as OptionPriceModel;
use Illuminate\Database\Eloquent\Builder;

final class EloquentOptionPriceUsageQuery implements OptionPriceUsageQuery
{
    public function isPricedAt(int $sizeTierId): bool
    {
        // Deleted options are left out automatically (SoftDeletes on the relation).
        return OptionPriceModel::query()
            ->where('size_tier_id', $sizeTierId)
            ->whereHas('option', fn (Builder $option) => $option->where('price_mode', PriceMode::PerSize->value))
            ->exists();
    }

    public function hasAnyPriceAt(int $sizeTierId): bool
    {
        return OptionPriceModel::query()->where('size_tier_id', $sizeTierId)->exists();
    }
}
