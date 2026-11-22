<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

final readonly class CreateKitchenStationInput
{
    public function __construct(public string $name) {}
}
