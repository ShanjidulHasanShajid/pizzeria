<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\Services;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * Turns a typed taka amount into Money, or into a form error under the given field.
 */
final class TakaInput
{
    public static function toMoney(?string $taka, string $field): Money
    {
        try {
            return Money::fromTaka($taka ?? '');
        } catch (InvalidValue $invalid) {
            throw ValidationFailed::forField($field, $invalid->getMessage());
        }
    }
}
