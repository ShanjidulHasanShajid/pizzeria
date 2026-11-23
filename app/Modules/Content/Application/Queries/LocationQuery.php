<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\Queries;

use App\Modules\Content\Application\DTOs\LocationView;

interface LocationQuery
{
    /**
     * The branches customers see, in order.
     *
     * @return list<LocationView>
     */
    public function active(): array;

    /**
     * Everything that is not deleted, for the admin list.
     *
     * @return list<LocationView>
     */
    public function table(): array;

    /** One location that is not deleted, or null. */
    public function location(int $id): ?LocationView;
}
