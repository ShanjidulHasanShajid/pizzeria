<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * One option to choose from. Either a flat price (the same for every size) or prices per size:
 * a size that is missing is "not offered at that size".
 */
final readonly class OptionView
{
    /**
     * @param  Money|null  $flatPrice  null when the option is priced per size
     * @param  array<int, Money>  $sizePrices  size tier id => price; empty when the price is flat
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $imagePath,
        public bool $isVeg,
        public bool $isAvailable,
        public ?Money $flatPrice,
        public array $sizePrices,
    ) {}
}
