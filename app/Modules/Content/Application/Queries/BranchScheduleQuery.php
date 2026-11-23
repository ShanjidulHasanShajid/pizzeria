<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\Queries;

use DateTimeImmutable;

/**
 * For other modules: when is a branch open? A branch that is unknown, deleted or switched off is
 * never open. Moments are in shop time, as the Clock port gives them.
 */
interface BranchScheduleQuery
{
    public function isOpenAt(int $locationId, DateTimeImmutable $moment): bool;

    /** The first opening after this moment, or null when the branch never opens. */
    public function nextOpeningAfter(int $locationId, DateTimeImmutable $moment): ?DateTimeImmutable;
}
