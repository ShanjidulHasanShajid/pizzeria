<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Adapters;

use App\Modules\Catalog\Application\DTOs\TrashedCatalogItem;
use App\Modules\Catalog\Application\Ports\OptionTrash;
use App\Modules\Customization\Application\DTOs\TrashedOptionItem;
use App\Modules\Customization\Application\Queries\TrashedOptionsQuery;

final class CustomizationOptionTrash implements OptionTrash
{
    public function __construct(private readonly TrashedOptionsQuery $options) {}

    public function items(): array
    {
        return array_map(fn (TrashedOptionItem $item): TrashedCatalogItem => new TrashedCatalogItem(
            kind: $item->isGroup ? 'option_group' : 'option',
            id: $item->id,
            name: $item->name,
            deletedAt: $item->deletedAt,
        ), $this->options->items());
    }
}
