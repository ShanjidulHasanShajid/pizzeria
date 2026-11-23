<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\Services;

use App\Modules\Content\Application\DTOs\SaveLocationInput;
use App\Modules\Content\Domain\ValueObjects\LocationDetails;
use App\Modules\Content\Domain\ValueObjects\OpeningHours;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;

/**
 * Shared by "create" and "update": reads the form and reports all its problems together.
 */
final class LocationInputReader
{
    /**
     * @return array{LocationDetails, OpeningHours}
     */
    public function read(SaveLocationInput $input): array
    {
        $errors = [];
        $details = null;
        $hours = null;

        try {
            $details = LocationDetails::fromInput(
                $input->name,
                $input->address,
                $input->area,
                $input->phone,
                $input->mapEmbed,
                $input->latitude,
                $input->longitude,
                $input->isPickupAvailable,
            );
        } catch (ValidationFailed $failure) {
            $errors = $failure->errors();
        }

        try {
            $hours = OpeningHours::fromWeek($input->hours);
        } catch (ValidationFailed $failure) {
            $errors = [...$errors, ...$failure->errors()];
        }

        if ($details === null || $hours === null) {
            throw ValidationFailed::withErrors($errors);
        }

        return [$details, $hours];
    }
}
