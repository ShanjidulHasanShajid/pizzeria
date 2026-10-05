<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;

/**
 * A percentage from 0 to 100 with two decimal places.
 * Stored as "basis points" (hundredths of a percent): 12.5% is 1250.
 * Whole numbers avoid floating point rounding errors.
 */
final readonly class Percentage
{
    private const MAX_BASIS_POINTS = 10_000;

    private function __construct(private int $basisPoints)
    {
        if ($basisPoints < 0 || $basisPoints > self::MAX_BASIS_POINTS) {
            throw InvalidValue::because('A percentage must be between 0 and 100.');
        }
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function fromBasisPoints(int $basisPoints): self
    {
        return new self($basisPoints);
    }

    /**
     * Accepts text such as "10", "12.5" or "33.33".
     */
    public static function fromString(string $value): self
    {
        if (preg_match('/^(\d{1,3})(?:\.(\d{1,2}))?$/', trim($value), $parts) !== 1) {
            throw InvalidValue::because('Enter a percentage such as 10 or 12.5.');
        }

        $decimals = str_pad($parts[2] ?? '', 2, '0');

        return new self(((int) $parts[1]) * 100 + (int) $decimals);
    }

    public function basisPoints(): int
    {
        return $this->basisPoints;
    }

    public function toString(): string
    {
        return sprintf('%d.%02d', intdiv($this->basisPoints, 100), $this->basisPoints % 100);
    }

    public function isZero(): bool
    {
        return $this->basisPoints === 0;
    }

    public function equals(self $other): bool
    {
        return $this->basisPoints === $other->basisPoints;
    }
}
