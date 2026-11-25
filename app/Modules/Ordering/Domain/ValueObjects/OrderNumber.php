<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;

/**
 * The number customers and the kitchen use: PZ-7K4M2Q (owner-decided 2026-11-25).
 * Six random characters, so it says nothing about how many orders there are and cannot be counted up.
 */
final readonly class OrderNumber
{
    public const PREFIX = 'PZ-';

    public const CODE_LENGTH = 6;

    /**
     * 28 characters: no 0, 1, I, L and O (easily mixed up), and no vowels (no accidental words).
     * That makes about 480 million numbers.
     */
    public const ALPHABET = '23456789BCDFGHJKMNPQRSTVWXYZ';

    private function __construct(private string $value) {}

    /**
     * From saved data or from a customer, who may type small letters, spaces or no "PZ-".
     * Null when it cannot be an order number.
     */
    public static function tryFrom(string $input): ?self
    {
        // Null for text that is not valid UTF-8; it counts as empty. \p{Pd} are all kinds of dashes.
        $code = strtoupper((string) preg_replace('/[\s\p{Pd}]+/u', '', $input));
        $prefix = rtrim(self::PREFIX, '-');

        if (strlen($code) === strlen($prefix) + self::CODE_LENGTH && str_starts_with($code, $prefix)) {
            $code = substr($code, strlen($prefix));
        }

        if (strlen($code) !== self::CODE_LENGTH || strspn($code, self::ALPHABET) !== self::CODE_LENGTH) {
            return null;
        }

        return new self(self::PREFIX.$code);
    }

    public static function fromString(string $input): self
    {
        return self::tryFrom($input) ?? throw InvalidValue::because('Enter an order number such as PZ-7K4M2Q.');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
