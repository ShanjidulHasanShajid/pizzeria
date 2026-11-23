<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\Services;

use App\Modules\Customization\Application\DTOs\ImageUpload;
use App\Modules\Shared\Application\Ports\FileStorage;
use App\Modules\Shared\Application\Ports\ImageProcessor;

/**
 * An option has one small square image (400 x 400, WebP), for the choice cards.
 */
final class OptionImages
{
    public const DIRECTORY = 'options';

    public const SIZE = 400;

    public function __construct(
        private readonly FileStorage $files,
        private readonly ImageProcessor $images,
    ) {}

    /** Returns the stored path. */
    public function store(ImageUpload $upload): string
    {
        $image = $this->images->toWebp($this->images->thumbnail($upload->contents, self::SIZE));

        return $this->files->store(self::DIRECTORY, $image, 'webp');
    }

    public function delete(string $path): void
    {
        $this->files->delete($path);
    }
}
