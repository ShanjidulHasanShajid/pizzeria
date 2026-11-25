<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Ports;

use App\Modules\Ordering\Application\DTOs\Branch;

/**
 * The branches of the shop (Content owns them).
 */
interface Branches
{
    /**
     * All that are not deleted, in order, also those that are switched off.
     *
     * @return list<Branch>
     */
    public function all(): array;

    /** One that is not deleted, or null. */
    public function find(int $id): ?Branch;
}
