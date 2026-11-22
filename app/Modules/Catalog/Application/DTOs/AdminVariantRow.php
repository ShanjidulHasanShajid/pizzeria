<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * One saved variant, as the product edit form shows it.
 */
final readonly class AdminVariantRow
{
    /**
     * @param  string  $priceTaka  for the form field, for example "450.00"
     */
    public function __construct(
        public int $id,
        public string $label,
        public string $priceTaka,
        public ?int $sizeTierId,
        public bool $isDefault,
        public bool $isAvailable,
    ) {}
}
