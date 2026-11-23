<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * One size or portion to choose from.
 */
final readonly class ConfigurableVariant
{
    public function __construct(
        public int $id,
        public string $label,
        public Money $price,
        public ?int $sizeTierId,
        public bool $isAvailable,
    ) {}
}
