<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;

/**
 * A Bangladesh mobile number, always stored as 01XXXXXXXXX (11 digits).
 * Accepts +8801..., 8801..., spaces, dashes and Bengali digits and normalizes them.
 */
final readonly class PhoneNumber
{
    private const BENGALI_DIGITS = [
        '০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4',
        '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9',
    ];

    private function __construct(private string $value) {}

    /**
     * Returns null when the input is not a valid number (no exception).
     * Useful for validation rules that only need yes or no.
     */
    public static function tryFrom(string $input): ?self
    {
        $digits = strtr(trim($input), self::BENGALI_DIGITS);
        $digits = (string) preg_replace('/[\s\-().]/', '', $digits);

        if (str_starts_with($digits, '+880')) {
            $digits = '0'.substr($digits, 4);
        } elseif (str_starts_with($digits, '880')) {
            $digits = '0'.substr($digits, 3);
        }

        if (preg_match('/^01[3-9]\d{8}$/', $digits) !== 1) {
            return null;
        }

        return new self($digits);
    }

    public static function fromString(string $input): self
    {
        return self::tryFrom($input)
            ?? throw InvalidValue::because('Enter a valid Bangladesh mobile number, for example 01712345678.');
    }

    /**
     * The normalized number: 01712345678.
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * International form for SMS gateways: +8801712345678.
     */
    public function international(): string
    {
        return '+88'.$this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
