<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

final readonly class ReorderLocationsInput
{
    /**
     * @param  list<int>  $ids  in their new order
     */
    public function __construct(public array $ids) {}
}
