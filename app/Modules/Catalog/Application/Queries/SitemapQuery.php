<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Queries;

use App\Modules\Catalog\Application\DTOs\SitemapEntry;

interface SitemapQuery
{
    /**
     * Every category and product customers can see.
     *
     * @return list<SitemapEntry>
     */
    public function entries(): array;
}
