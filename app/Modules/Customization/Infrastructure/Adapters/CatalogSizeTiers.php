<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Adapters;

use App\Modules\Catalog\Application\DTOs\SizeTierRow;
use App\Modules\Catalog\Application\Queries\AdminCatalogQuery;
use App\Modules\Customization\Application\Ports\SizeTiers;

final class CatalogSizeTiers implements SizeTiers
{
    public function __construct(private readonly AdminCatalogQuery $catalog) {}

    public function ids(): array
    {
        return array_map(fn (SizeTierRow $row): int => $row->id, $this->catalog->sizeTierTable());
    }
}
