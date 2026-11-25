<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\DTOs;

final readonly class RemoveLineInput
{
    public function __construct(public int $lineId) {}
}
