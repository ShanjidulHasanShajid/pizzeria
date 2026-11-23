<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

/**
 * One option as the admin screens show it. Prices are taka texts for form fields.
 */
final readonly class AdminOptionRow
{
    /**
     * @param  ?string  $flatPriceTaka  for example "60.00"; null when it never had a flat price
     * @param  array<int, string>  $sizePricesTaka  size tier id => for example "150.00"
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $imagePath,
        public bool $isVeg,
        public bool $isAvailable,
        public bool $isPricedPerSize,
        public ?string $flatPriceTaka,
        public array $sizePricesTaka,
    ) {}
}
