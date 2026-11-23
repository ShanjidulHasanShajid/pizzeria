<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

/**
 * The bytes of an uploaded image. The form has already checked that it is a JPG, PNG or WebP
 * of the right size.
 */
final readonly class ImageUpload
{
    public function __construct(public string $contents) {}
}
