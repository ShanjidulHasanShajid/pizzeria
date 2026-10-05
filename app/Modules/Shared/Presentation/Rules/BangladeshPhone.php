<?php

declare(strict_types=1);

namespace App\Modules\Shared\Presentation\Rules;

use App\Modules\Shared\Domain\ValueObjects\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Use in a Form Request or Livewire component:
 *   'phone' => ['required', new BangladeshPhone],
 * It accepts anything the PhoneNumber value object accepts.
 */
final class BangladeshPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || PhoneNumber::tryFrom($value) === null) {
            $fail('The :attribute must be a valid Bangladesh mobile number, for example 01712345678.');
        }
    }
}
