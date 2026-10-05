<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;

/**
 * Position in a list. Values go up in steps of 10 (10, 20, 30) so an admin
 * can insert an item between two others without renumbering everything.
 */
final readonly class SortOrder
{
    public const STEP = 10;

    private function __construct(private int $value)
    {
        if ($value < 0) {
            throw InvalidValue::because('A sort order cannot be negative.');
        }
    }

    public static function first(): self
    {
        return new self(self::STEP);
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function next(): self
    {
        return new self($this->value + self::STEP);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function isBefore(self $other): bool
    {
        return $this->value < $other->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
