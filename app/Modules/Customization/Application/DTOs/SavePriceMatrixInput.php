<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

final readonly class SavePriceMatrixInput
{
    /**
     * @param  array<int, array<int, ?string>>  $cells  option id => size tier id => taka as typed;
     *                                                  null or "" = not offered at that size, "0" = free
     */
    public function __construct(
        public int $optionGroupId,
        public array $cells,
    ) {}
}
