<?php

declare(strict_types=1);

namespace App\Modules\Shared\Presentation\Support;

use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use Illuminate\Validation\ValidationException;

/**
 * Turns a business-rule error into the kind of error Laravel forms and Livewire understand.
 *
 * Usage in a controller or Livewire action:
 *
 *   try {
 *       $useCase->handle($input);
 *   } catch (ValidationFailed $failure) {
 *       throw $translator->toValidationException($failure);
 *   }
 */
final class ValidationFailedTranslator
{
    public function toValidationException(ValidationFailed $failure): ValidationException
    {
        return ValidationException::withMessages($failure->errors());
    }
}
