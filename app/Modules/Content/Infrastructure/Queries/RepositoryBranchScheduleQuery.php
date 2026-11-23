<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Queries;

use App\Modules\Content\Application\Queries\BranchScheduleQuery;
use App\Modules\Content\Domain\Repositories\LocationRepository;
use App\Modules\Content\Domain\ValueObjects\OpeningHours;
use DateTimeImmutable;

final class RepositoryBranchScheduleQuery implements BranchScheduleQuery
{
    public function __construct(private readonly LocationRepository $locations) {}

    public function isOpenAt(int $locationId, DateTimeImmutable $moment): bool
    {
        return $this->hours($locationId)->isOpenAt($moment);
    }

    public function nextOpeningAfter(int $locationId, DateTimeImmutable $moment): ?DateTimeImmutable
    {
        return $this->hours($locationId)->nextOpeningAfter($moment);
    }

    private function hours(int $locationId): OpeningHours
    {
        foreach ($this->locations->all() as $location) {
            if ($location->id === $locationId && $location->isActive) {
                return $location->hours;
            }
        }

        return OpeningHours::alwaysClosed();
    }
}
