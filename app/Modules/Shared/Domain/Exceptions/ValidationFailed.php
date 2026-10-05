<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Exceptions;

/**
 * Carries one or more messages per form field, for example
 * ['phone' => ['Enter a valid mobile number.']].
 * The Presentation layer turns it into a Laravel validation error.
 */
final class ValidationFailed extends DomainException
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    private function __construct(private readonly array $errors)
    {
        parent::__construct('The given data is not valid.');
    }

    public static function forField(string $field, string $message): self
    {
        return new self([$field => [$message]]);
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    public static function withErrors(array $errors): self
    {
        return new self($errors);
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
