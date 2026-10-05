<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;

/**
 * A valid e-mail address, trimmed and lower-cased.
 */
final readonly class EmailAddress
{
    private const MAX_LENGTH = 254;

    private function __construct(private string $value)
    {
        if (strlen($value) > self::MAX_LENGTH || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidValue::because('Enter a valid e-mail address.');
        }
    }

    public static function fromString(string $value): self
    {
        return new self(mb_strtolower(trim($value)));
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
