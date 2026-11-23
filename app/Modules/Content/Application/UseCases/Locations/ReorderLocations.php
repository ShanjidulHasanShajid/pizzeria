<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Locations;

use App\Modules\Content\Application\DTOs\ReorderLocationsInput;
use App\Modules\Content\Domain\Repositories\LocationRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

final class ReorderLocations
{
    public function __construct(
        private readonly LocationRepository $locations,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(ReorderLocationsInput $input): void
    {
        if (count(array_unique($input->ids)) !== count($input->ids)) {
            throw ValidationFailed::forField('ids', 'Each location may appear only once.');
        }

        $this->transactions->run(function () use ($input): void {
            $sort = SortOrder::first();

            foreach ($input->ids as $id) {
                $location = $this->locations->find($id) ?? throw EntityNotFound::withId('Location', $id);
                $this->locations->save($location->movedTo($sort));
                $sort = $sort->next();
            }
        });
    }
}
