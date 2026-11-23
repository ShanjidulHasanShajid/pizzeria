<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;

/**
 * A point on the map. Kept as text with at most 7 decimals, as the database stores it.
 */
final readonly class Coordinates
{
    private function __construct(
        public string $latitude,
        public string $longitude,
    ) {}

    public static function fromStrings(string $latitude, string $longitude): self
    {
        return new self(
            self::checked($latitude, 90, 'The latitude is a number from -90 to 90, for example 23.7465.'),
            self::checked($longitude, 180, 'The longitude is a number from -180 to 180, for example 90.3760.'),
        );
    }

    private static function checked(string $value, int $limit, string $message): string
    {
        $value = trim($value);

        if (preg_match('/^-?\d{1,3}(\.\d{1,20})?$/D', $value) !== 1 || abs((float) $value) > $limit) {
            throw InvalidValue::because($message);
        }

        return rtrim(rtrim(sprintf('%.7F', (float) $value), '0'), '.');
    }
}
