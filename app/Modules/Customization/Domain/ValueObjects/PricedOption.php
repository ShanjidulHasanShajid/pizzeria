<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * One chosen option with its price: what it costs at this size, and what the customer pays
 * (zero when it is one of the free picks of its group).
 */
final readonly class PricedOption
{
    public function __construct(
        private int $optionGroupId,
        private string $groupTitle,
        private int $optionId,
        private string $optionName,
        private Money $price,
        private Money $charged,
    ) {
        if ($charged->isGreaterThan($price)) {
            throw InvalidValue::because('An option cannot cost more than its price.');
        }
    }

    public function optionGroupId(): int
    {
        return $this->optionGroupId;
    }

    public function groupTitle(): string
    {
        return $this->groupTitle;
    }

    public function optionId(): int
    {
        return $this->optionId;
    }

    public function optionName(): string
    {
        return $this->optionName;
    }

    /** The option's price for the chosen size. */
    public function price(): Money
    {
        return $this->price;
    }

    /** What the customer pays for it. */
    public function charged(): Money
    {
        return $this->charged;
    }

    /** True when the group's free count made a paid option free. */
    public function isFreePick(): bool
    {
        return $this->charged->isZero() && ! $this->price->isZero();
    }
}
