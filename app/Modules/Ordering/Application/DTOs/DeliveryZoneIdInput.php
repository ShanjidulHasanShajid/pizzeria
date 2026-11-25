<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\DTOs;

final readonly class DeliveryZoneIdInput
{
    public function __construct(public int $id) {}
}
