<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Exceptions;

/**
 * A repository could not find the record that was asked for.
 */
final class EntityNotFound extends DomainException
{
    public static function withId(string $entity, int|string $id): self
    {
        return new self(sprintf('%s with id %s was not found.', $entity, $id));
    }
}
