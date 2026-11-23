<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\Services;

use App\Modules\Content\Domain\Enums\SettingKey;
use App\Modules\Content\Domain\Enums\SettingType;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\EmailAddress;
use App\Modules\Shared\Domain\ValueObjects\Percentage;

/**
 * Checks and tidies a typed setting value, by the type of the setting.
 */
final class SettingValues
{
    public const TEXT_MAX_LENGTH = 150;

    public const LONG_TEXT_MAX_LENGTH = 500;

    public const URL_MAX_LENGTH = 255;

    public const INTEGER_MIN = 1;

    public const INTEGER_MAX = 24;

    /**
     * The value as it is stored.
     *
     * @throws InvalidValue when it does not fit the type of the setting
     */
    public function clean(SettingKey $key, string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            if ($key->isRequired()) {
                throw InvalidValue::because('This setting cannot be empty.');
            }

            return $key->type() === SettingType::Boolean ? '0' : '';
        }

        return match ($key->type()) {
            SettingType::Text => $this->text($value, self::TEXT_MAX_LENGTH),
            SettingType::LongText => $this->text($value, self::LONG_TEXT_MAX_LENGTH),
            SettingType::Phone => $this->phone($value),
            SettingType::Email => EmailAddress::fromString($value)->value(),
            SettingType::Url => $this->url($value),
            SettingType::Integer => $this->integer($value),
            SettingType::Percentage => Percentage::fromString($value)->toString(),
            SettingType::Boolean => $this->boolean($value),
            SettingType::Image => throw InvalidValue::because('An image is uploaded, not typed.'),
        };
    }

    private function text(string $value, int $maxLength): string
    {
        if (mb_strlen($value) > $maxLength) {
            throw InvalidValue::because("Use at most {$maxLength} characters.");
        }

        return $value;
    }

    /** A shop number may be a landline or a short hotline number, so it is not a PhoneNumber. */
    private function phone(string $value): string
    {
        if (preg_match('/^\+?[0-9][0-9 ()-]{3,23}$/', $value) !== 1) {
            throw InvalidValue::because('Enter a phone number such as 01712 345678 or 16247.');
        }

        return $value;
    }

    private function url(string $value): string
    {
        if (strlen($value) > self::URL_MAX_LENGTH || ! str_starts_with($value, 'https://') || filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw InvalidValue::because('Enter a full address that starts with https://');
        }

        return $value;
    }

    private function integer(string $value): string
    {
        if (preg_match('/^\d{1,3}$/', $value) !== 1 || (int) $value < self::INTEGER_MIN || (int) $value > self::INTEGER_MAX) {
            throw InvalidValue::because(sprintf('Enter a whole number from %d to %d.', self::INTEGER_MIN, self::INTEGER_MAX));
        }

        return (string) (int) $value;
    }

    private function boolean(string $value): string
    {
        if ($value !== '0' && $value !== '1') {
            throw InvalidValue::because('Choose yes or no.');
        }

        return $value;
    }
}
