<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

final readonly class AddGalleryImagesInput
{
    /**
     * @param  list<ImageUpload>  $images
     */
    public function __construct(public array $images) {}
}
