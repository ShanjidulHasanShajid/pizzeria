<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\DomainException;

/**
 * A product's images break a rule: one main image when there are images, no image twice.
 */
final class InvalidProductImages extends DomainException
{
    public static function notExactlyOneMain(): self
    {
        return new self('Exactly one image must be the main image.');
    }

    public static function duplicate(string $path): self
    {
        return new self("The image \"{$path}\" is already on this product.");
    }

    public static function notFound(string $path): self
    {
        return new self("This product has no image \"{$path}\".");
    }

    public static function reorderNeedsEveryImage(): self
    {
        return new self('To reorder the images, list every image of the product exactly once.');
    }
}
