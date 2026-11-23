<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\ValueObjects;

use App\Modules\Content\Domain\Enums\SettingKey;
use App\Modules\Content\Domain\Services\SettingValues;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use Closure;

/**
 * Where a branch is and how to reach it.
 */
final readonly class LocationDetails
{
    private function __construct(
        public string $name,
        public string $address,
        public string $area,
        public string $phone,
        public string $mapEmbedUrl,
        public ?Coordinates $coordinates,
        public bool $isPickupAvailable,
    ) {}

    /**
     * From a form: everything is checked and all problems are reported together.
     */
    public static function fromInput(
        string $name,
        string $address,
        string $area,
        string $phone,
        string $mapEmbed,
        string $latitude,
        string $longitude,
        bool $isPickupAvailable,
    ): self {
        $errors = [];
        $try = function (string $field, Closure $check) use (&$errors): mixed {
            try {
                return $check();
            } catch (InvalidValue $invalid) {
                $errors[$field] = [$invalid->getMessage()];

                return null;
            }
        };

        $name = $try('name', fn (): string => self::text($name, 100, true));
        $address = $try('address', fn (): string => self::text($address, 255, true));
        $area = $try('area', fn (): string => self::text($area, 100, false));
        $phone = $try('phone', fn (): string => self::phone($phone));
        $mapEmbedUrl = $try('map_embed_url', fn (): string => trim($mapEmbed) === '' ? '' : MapEmbedUrl::fromInput($mapEmbed)->value());
        $coordinates = $try('latitude', fn (): ?Coordinates => trim($latitude.$longitude) === '' ? null : Coordinates::fromStrings($latitude, $longitude));

        if ($errors !== []) {
            throw ValidationFailed::withErrors($errors);
        }

        return new self($name, $address, $area, $phone, $mapEmbedUrl, $coordinates, $isPickupAvailable);
    }

    /** Rebuilds saved details. Only the repository and tests call this. */
    public static function reconstitute(
        string $name,
        string $address,
        string $area,
        string $phone,
        string $mapEmbedUrl,
        ?Coordinates $coordinates,
        bool $isPickupAvailable,
    ): self {
        return new self($name, $address, $area, $phone, $mapEmbedUrl, $coordinates, $isPickupAvailable);
    }

    private static function text(string $value, int $maxLength, bool $isRequired): string
    {
        $value = trim($value);

        if ($value === '' && $isRequired) {
            throw InvalidValue::because('This field is required.');
        }

        if (mb_strlen($value) > $maxLength) {
            throw InvalidValue::because("Use at most {$maxLength} characters.");
        }

        return $value;
    }

    /** The same rule as the phone number of the shop in the settings. */
    private static function phone(string $value): string
    {
        return self::text($value, 25, true) === '' ? '' : (new SettingValues)->clean(SettingKey::Phone, $value);
    }
}
