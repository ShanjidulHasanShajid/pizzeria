<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * One size or portion on the product page.
 */
final readonly class VariantView
{
    public function __construct(
        public int $id,
        public string $label,
        public Money $price,
        public ?int $sizeTierId,
        public ?string $sizeTierName,
        public bool $isDefault,
        public bool $isAvailable,
    ) {}
}
