<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

use App\Modules\Customization\Domain\ValueObjects\PricedOption;
use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * One chosen option of a priced line.
 */
final readonly class PricedOptionView
{
    public function __construct(
        public int $optionGroupId,
        public string $groupTitle,
        public int $optionId,
        public string $optionName,
        public Money $price,
        public Money $charged,
        public bool $isFreePick,
    ) {}

    public static function fromPricedOption(PricedOption $option): self
    {
        return new self(
            $option->optionGroupId(),
            $option->groupTitle(),
            $option->optionId(),
            $option->optionName(),
            $option->price(),
            $option->charged(),
            $option->isFreePick(),
        );
    }
}
