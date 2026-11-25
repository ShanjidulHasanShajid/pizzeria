<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\UseCases\DeliveryZones;

use App\Modules\Ordering\Application\DTOs\ReorderDeliveryZonesInput;
use App\Modules\Ordering\Domain\Repositories\DeliveryZoneRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * Saves the order the admin dragged: the first id gets sort 10, the next 20, and so on.
 */
final class ReorderDeliveryZones
{
    public function __construct(
        private readonly DeliveryZoneRepository $zones,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(ReorderDeliveryZonesInput $input): void
    {
        if (count(array_unique($input->ids)) !== count($input->ids)) {
            throw ValidationFailed::forField('ids', 'Each zone may appear only once.');
        }

        $this->transactions->run(function () use ($input): void {
            $sort = SortOrder::first();

            foreach ($input->ids as $id) {
                $zone = $this->zones->find($id) ?? throw EntityNotFound::withId('Delivery zone', $id);

                $zone->moveTo($sort);
                $this->zones->save($zone);
                $sort = $sort->next();
            }
        });
    }
}
