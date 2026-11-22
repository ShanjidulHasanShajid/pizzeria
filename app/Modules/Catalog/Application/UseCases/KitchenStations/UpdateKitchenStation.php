<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\KitchenStations;

use App\Modules\Catalog\Application\DTOs\UpdateKitchenStationInput;
use App\Modules\Catalog\Domain\Repositories\KitchenStationRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

final class UpdateKitchenStation
{
    public function __construct(private readonly KitchenStationRepository $stations) {}

    public function handle(UpdateKitchenStationInput $input): void
    {
        $station = $this->stations->find($input->id)
            ?? throw EntityNotFound::withId('Kitchen station', $input->id);

        $station->rename($input->name);
        if ($input->isActive) {
            $station->activate();
        } else {
            $station->deactivate();
        }

        $this->stations->save($station);
    }
}
