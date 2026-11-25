<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Services;

use App\Modules\Ordering\Application\DTOs\SaveDeliveryZoneInput;
use App\Modules\Ordering\Application\Ports\Branches;
use App\Modules\Ordering\Domain\ValueObjects\DeliveryZoneDetails;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;

/**
 * Turns the form into checked details. The same for creating and changing a zone.
 */
final class DeliveryZoneInputReader
{
    public function __construct(private readonly Branches $branches) {}

    public function read(SaveDeliveryZoneInput $input): DeliveryZoneDetails
    {
        $errors = [];
        $details = null;

        try {
            $details = DeliveryZoneDetails::fromInput($input->name, $input->locationId, $input->feeTaka, $input->minimumOrderTaka);
        } catch (ValidationFailed $failure) {
            $errors = $failure->errors();
        }

        if (! isset($errors['location_id']) && $this->branches->find($input->locationId) === null) {
            $errors['location_id'] = ['Choose a branch of the list.'];
        }

        if ($details === null || $errors !== []) {
            throw ValidationFailed::withErrors($errors);
        }

        return $details;
    }
}
