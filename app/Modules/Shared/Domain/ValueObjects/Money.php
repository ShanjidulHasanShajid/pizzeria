<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;

/**
 * An amount of Bangladeshi taka, stored as whole poisha (100 poisha = 1 taka).
 * Never use float for money. Money is never negative.
 */
final readonly class Money
{
    public const CURRENCY = 'BDT';

    private function __construct(private int $poisha)
    {
        if ($poisha < 0) {
            throw InvalidValue::because('Money cannot be negative.');
        }
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function fromPoisha(int $poisha): self
    {
        return new self($poisha);
    }

    /**
     * For admin forms. Accepts "250", "250.5" or "250.50" (text or a whole number).
     * It reads the text itself instead of converting to float.
     */
    public static function fromTaka(string|int $taka): self
    {
        $text = trim((string) $taka);

        if (preg_match('/^(\d{1,12})(?:\.(\d{1,2}))?$/', $text, $parts) !== 1) {
            throw InvalidValue::because('Enter an amount in taka such as 250 or 250.50.');
        }

        $poishaPart = str_pad($parts[2] ?? '', 2, '0');

        return new self(((int) $parts[1]) * 100 + (int) $poishaPart);
    }

    public function poisha(): int
    {
        return $this->poisha;
    }

    public function currency(): string
    {
        return self::CURRENCY;
    }

    /**
     * For admin forms: 125050 poisha becomes "1250.50".
     */
    public function toTakaString(): string
    {
        return sprintf('%d.%02d', intdiv($this->poisha, 100), $this->poisha % 100);
    }

    public function add(self $other): self
    {
        return new self($this->poisha + $other->poisha);
    }

    public function subtract(self $other): self
    {
        if ($other->poisha > $this->poisha) {
            throw InvalidValue::because('Cannot subtract more money than there is.');
        }

        return new self($this->poisha - $other->poisha);
    }

    public function multiply(Quantity $quantity): self
    {
        return new self($this->poisha * $quantity->value());
    }

    /**
     * This percentage of the amount, rounded half up to whole poisha.
     * Example: 10% of 335 poisha is 33.5, which becomes 34.
     */
    public function percentage(Percentage $percentage): self
    {
        return new self(intdiv($this->poisha * $percentage->basisPoints() + 5_000, 10_000));
    }

    public function isZero(): bool
    {
        return $this->poisha === 0;
    }

    public function equals(self $other): bool
    {
        return $this->poisha === $other->poisha;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->poisha > $other->poisha;
    }

    public function isLessThan(self $other): bool
    {
        return $this->poisha < $other->poisha;
    }
}
