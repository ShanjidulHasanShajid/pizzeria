<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

use App\Modules\Customization\Domain\Enums\PriceMode;

final readonly class AddOptionInput
{
    /**
     * @param  ?string  $flatPriceTaka  as typed, for example "60"; only used when the price is flat
     */
    public function __construct(
        public int $optionGroupId,
        public string $name,
        public bool $isVeg,
        public PriceMode $priceMode,
        public ?string $flatPriceTaka,
    ) {}
}
