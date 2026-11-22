<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\SizeTiers;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Repositories\SizeTierRepository;

final class RestoreSizeTier
{
    public function __construct(private readonly SizeTierRepository $sizeTiers) {}

    public function handle(CatalogItemInput $input): void
    {
        $this->sizeTiers->restore($input->id);
    }
}
