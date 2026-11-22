<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\KitchenStations;

use App\Modules\Catalog\Application\DTOs\CreateKitchenStationInput;
use App\Modules\Catalog\Domain\Entities\KitchenStation;
use App\Modules\Catalog\Domain\Repositories\KitchenStationRepository;

/**
 * Adds a station at the end of the list. Returns its id.
 */
final class CreateKitchenStation
{
    public function __construct(private readonly KitchenStationRepository $stations) {}

    public function handle(CreateKitchenStationInput $input): int
    {
        return $this->stations->save(
            KitchenStation::create($input->name, $this->stations->nextSortOrder()),
        );
    }
}
