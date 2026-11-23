<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\Queries;

use App\Modules\Customization\Application\DTOs\TrashedOptionItem;

interface TrashedOptionsQuery
{
    /**
     * Deleted option groups, and options that were deleted one by one.
     *
     * @return list<TrashedOptionItem>
     */
    public function items(): array;
}
