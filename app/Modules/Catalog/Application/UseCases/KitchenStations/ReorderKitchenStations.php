<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\KitchenStations;

use App\Modules\Catalog\Application\DTOs\ReorderInput;
use App\Modules\Catalog\Domain\Repositories\KitchenStationRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * Saves the order the admin dragged: the first id gets sort 10, the next 20, and so on.
 * All in one transaction, so a failure never leaves half a new order behind.
 */
final class ReorderKitchenStations
{
    public function __construct(
        private readonly KitchenStationRepository $stations,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(ReorderInput $input): void
    {
        if (count(array_unique($input->ids)) !== count($input->ids)) {
            throw ValidationFailed::forField('ids', 'Each station may appear only once.');
        }

        $this->transactions->run(function () use ($input): void {
            $sort = SortOrder::first();

            foreach ($input->ids as $id) {
                $station = $this->stations->find($id)
                    ?? throw EntityNotFound::withId('Kitchen station', $id);

                $station->moveTo($sort);
                $this->stations->save($station);
                $sort = $sort->next();
            }
        });
    }
}
