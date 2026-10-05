<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;

/**
 * How many of something: a whole number from 1 to 20 (business rule for a cart line).
 */
final readonly class Quantity
{
    public const MIN = 1;

    public const MAX = 20;

    private function __construct(private int $value)
    {
        if ($value < self::MIN || $value > self::MAX) {
            throw InvalidValue::because(sprintf('Quantity must be between %d and %d.', self::MIN, self::MAX));
        }
    }

    public static function of(int $value): self
    {
        return new self($value);
    }

    public function add(self $other): self
    {
        return new self($this->value + $other->value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
