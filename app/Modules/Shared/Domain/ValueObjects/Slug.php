<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;

/**
 * The URL-friendly name of something: "margherita-pizza".
 * Lower-case letters and digits, single hyphens between them, up to 120 characters.
 * This class only checks. To build a slug from a title, use the SlugGenerator service.
 */
final readonly class Slug
{
    public const MAX_LENGTH = 120;

    private const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private function __construct(private string $value)
    {
        if (strlen($value) > self::MAX_LENGTH || preg_match(self::PATTERN, $value) !== 1) {
            throw InvalidValue::because(
                'A slug may contain only lower-case letters, numbers and single hyphens (up to 120 characters).',
            );
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
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
