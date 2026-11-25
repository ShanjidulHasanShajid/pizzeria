<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\DTOs;

final readonly class ReorderDeliveryZonesInput
{
    /**
     * @param  list<int>  $ids  in their new order
     */
    public function __construct(public array $ids) {}
}
