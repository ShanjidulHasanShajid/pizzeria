<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\Services;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use Closure;

/**
 * Checks the fields of a form one by one and reports all problems together.
 * Ask throwIfAnyFailed() before the checked values are used.
 */
final class FieldChecker
{
    /** @var array<string, list<string>> */
    private array $errors = [];

    /**
     * Runs a check that may throw InvalidValue. The result is null when it did.
     *
     * @template TValue
     *
     * @param  Closure(): TValue  $check
     * @return TValue|null
     */
    public function check(string $field, Closure $check): mixed
    {
        try {
            return $check();
        } catch (InvalidValue $invalid) {
            $this->errors[$field][] = $invalid->getMessage();

            return null;
        }
    }

    /** One line of text that must be there. Several spaces become one. */
    public function line(string $field, string $text, int $maxLength): string
    {
        // Null for text that is not valid UTF-8; it counts as empty.
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return $this->filled($field, $this->limited($field, $text, $maxLength));
    }

    /** Text that may have several lines and must be there. */
    public function text(string $field, string $text, int $maxLength): string
    {
        return $this->filled($field, $this->optionalText($field, $text, $maxLength));
    }

    /** Text that may have several lines and may be empty. */
    public function optionalText(string $field, string $text, int $maxLength): string
    {
        $text = mb_check_encoding($text, 'UTF-8') ? $text : '';

        return $this->limited($field, trim(str_replace(["\r\n", "\r"], "\n", $text)), $maxLength);
    }

    public function fail(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function throwIfAnyFailed(): void
    {
        if ($this->errors !== []) {
            throw ValidationFailed::withErrors($this->errors);
        }
    }

    private function filled(string $field, string $text): string
    {
        if ($text === '') {
            $this->fail($field, 'This field is required.');
        }

        return $text;
    }

    private function limited(string $field, string $text, int $maxLength): string
    {
        if (mb_strlen($text) > $maxLength) {
            $this->fail($field, "Use at most {$maxLength} characters.");
        }

        return $text;
    }
}
