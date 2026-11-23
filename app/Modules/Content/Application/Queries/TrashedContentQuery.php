<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\Queries;

use App\Modules\Content\Application\DTOs\TrashedContentItem;

interface TrashedContentQuery
{
    /**
     * Deleted hero slides, banners, trust badges, gallery images and locations.
     *
     * @return list<TrashedContentItem>
     */
    public function items(): array;
}
