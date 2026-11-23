<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Queries;

use App\Modules\Content\Application\DTOs\LocationView;
use App\Modules\Content\Application\Queries\LocationQuery;
use App\Modules\Content\Domain\Repositories\LocationRepository;
use App\Modules\Content\Domain\ValueObjects\Location;
use App\Modules\Shared\Application\Ports\Clock;

final class RepositoryLocationQuery implements LocationQuery
{
    public function __construct(
        private readonly LocationRepository $locations,
        private readonly Clock $clock,
    ) {}

    public function active(): array
    {
        return array_values(array_filter($this->table(), fn (LocationView $location): bool => $location->isActive));
    }

    public function table(): array
    {
        $now = $this->clock->now();

        return array_map(fn (Location $location): LocationView => LocationView::fromLocation($location, $now), $this->locations->all());
    }

    public function location(int $id): ?LocationView
    {
        $location = $this->locations->find($id);

        return $location === null ? null : LocationView::fromLocation($location, $this->clock->now());
    }
}
