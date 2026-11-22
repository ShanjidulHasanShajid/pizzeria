<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\KitchenStations;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Exceptions\CatalogItemInUse;
use App\Modules\Catalog\Domain\Repositories\KitchenStationRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Moves a station to the Trash. Refused while a category uses it, because the
 * database cannot see a soft delete and would not stop it (business rules section 3).
 */
final class DeleteKitchenStation
{
    public function __construct(
        private readonly KitchenStationRepository $stations,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(CatalogItemInput $input): void
    {
        $this->transactions->run(function () use ($input): void {
            $station = $this->stations->find($input->id)
                ?? throw EntityNotFound::withId('Kitchen station', $input->id);

            if ($this->stations->isUsedByCategories($input->id)) {
                throw CatalogItemInUse::kitchenStation($station->name());
            }

            $this->stations->delete($input->id);
        });
    }
}
