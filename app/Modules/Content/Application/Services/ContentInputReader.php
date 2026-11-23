<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\Services;

use App\Modules\Content\Application\DTOs\ImageUpload;
use App\Modules\Content\Application\DTOs\SaveContentItemInput;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\DateRange;

/**
 * Shared by "create" and "update": reads the parts of the form that both need.
 */
final class ContentInputReader
{
    /**
     * The typed fields only. A path of an image can never come from the browser.
     *
     * @return array<string, string>
     */
    public function texts(SaveContentItemInput $input): array
    {
        $texts = [];

        foreach ($input->type->fields() as $field) {
            if (! $field->isImage()) {
                $texts[$field->name] = $input->values[$field->name] ?? '';
            }
        }

        return $texts;
    }

    /**
     * Uploads for image fields this type really has.
     *
     * @return array<string, ImageUpload>
     */
    public function uploads(SaveContentItemInput $input): array
    {
        $uploads = [];

        foreach ($input->type->fields() as $field) {
            if ($field->isImage() && isset($input->uploads[$field->name])) {
                $uploads[$field->name] = $input->uploads[$field->name];
            }
        }

        return $uploads;
    }

    public function schedule(SaveContentItemInput $input): DateRange
    {
        try {
            return DateRange::between($input->startsAt, $input->endsAt);
        } catch (InvalidValue) {
            throw ValidationFailed::forField('ends_at', 'The end cannot be before the start.');
        }
    }
}
