<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * The bytes of an image the admin uploaded. The form request has already checked
 * that it is a JPG, PNG or WebP of the right size, so the use case can trust the format.
 */
final readonly class ImageUpload
{
    public function __construct(public string $contents) {}
}
