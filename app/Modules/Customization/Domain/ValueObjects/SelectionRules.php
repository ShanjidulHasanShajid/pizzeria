<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;

/**
 * How many options a customer may pick from one group on one product.
 * - Required: at least the minimum (which is then 1 or more).
 * - Optional: none at all, or between the minimum and the maximum.
 * - Free count: that many of the cheapest picks cost nothing.
 */
final readonly class SelectionRules
{
    /** More picks than this from one group is almost certainly a typing mistake. */
    public const MAX_PICKS = 30;

    private function __construct(
        private bool $isRequired,
        private int $minimum,
        private int $maximum,
        private int $freeCount,
    ) {
        if ($maximum < 1 || $maximum > self::MAX_PICKS) {
            throw InvalidValue::because(sprintf('The maximum must be between 1 and %d.', self::MAX_PICKS));
        }

        if ($minimum < 0 || $minimum > $maximum) {
            throw InvalidValue::because('The minimum must be between 0 and the maximum.');
        }

        if ($isRequired && $minimum < 1) {
            throw InvalidValue::because('A required group needs a minimum of at least 1.');
        }

        if ($freeCount < 0 || $freeCount > $maximum) {
            throw InvalidValue::because('The number of free picks must be between 0 and the maximum.');
        }
    }

    public static function of(bool $isRequired, int $minimum, int $maximum, int $freeCount = 0): self
    {
        return new self($isRequired, $minimum, $maximum, $freeCount);
    }

    /** Pick exactly one, for example a crust. */
    public static function exactlyOne(): self
    {
        return new self(true, 1, 1, 0);
    }

    /** Pick none, or up to $maximum, for example extra toppings. */
    public static function upTo(int $maximum, int $freeCount = 0): self
    {
        return new self(false, 0, $maximum, $freeCount);
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function minimum(): int
    {
        return $this->minimum;
    }

    public function maximum(): int
    {
        return $this->maximum;
    }

    public function freeCount(): int
    {
        return $this->freeCount;
    }

    public function equals(self $other): bool
    {
        return $this->isRequired === $other->isRequired
            && $this->minimum === $other->minimum
            && $this->maximum === $other->maximum
            && $this->freeCount === $other->freeCount;
    }
}
