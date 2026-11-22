<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\KitchenStations;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Repositories\KitchenStationRepository;

/**
 * Brings a station back from the Trash. An id that is not in the Trash fails with "not found".
 */
final class RestoreKitchenStation
{
    public function __construct(private readonly KitchenStationRepository $stations) {}

    public function handle(CatalogItemInput $input): void
    {
        $this->stations->restore($input->id);
    }
}
