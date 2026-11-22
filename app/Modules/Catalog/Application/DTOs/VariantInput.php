<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * One variant row of the product form (Personal ৳450, 6 pcs ৳390 ...).
 */
final readonly class VariantInput
{
    /**
     * @param  ?int  $id  the saved variant this row edits; null for a new row
     */
    public function __construct(
        public ?int $id,
        public string $label,
        public Money $price,
        public ?int $sizeTierId,
        public bool $isDefault,
        public bool $isAvailable,
    ) {}
}
