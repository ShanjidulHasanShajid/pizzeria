<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Exceptions;

/**
 * Thrown by a value object when it is given a value it cannot accept,
 * for example Money::fromPoisha(-5) or PhoneNumber::fromString('abc').
 */
final class InvalidValue extends DomainException
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
