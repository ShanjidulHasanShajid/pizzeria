<?php

declare(strict_types=1);

namespace App\Modules\Shared\Presentation\Support;

/**
 * A module with deletable records implements this and is added to the list in AppServiceProvider.
 * The Trash screen itself knows no module.
 */
interface TrashSource
{
    /**
     * @return list<TrashKind>
     */
    public function kinds(): array;

    /**
     * Everything of these kinds that is deleted, in any order.
     *
     * @return list<TrashItem>
     */
    public function items(): array;
}
