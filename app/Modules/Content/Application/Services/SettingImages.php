<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\Services;

use App\Modules\Content\Application\DTOs\ImageUpload;
use App\Modules\Content\Domain\Enums\SettingKey;
use App\Modules\Shared\Application\Ports\FileStorage;
use App\Modules\Shared\Application\Ports\ImageProcessor;

/**
 * The logo keeps its shape and fits into 600 x 240; the favicon is a 128 x 128 square. Both WebP.
 */
final class SettingImages
{
    public const DIRECTORY = 'settings';

    public function __construct(
        private readonly FileStorage $files,
        private readonly ImageProcessor $images,
    ) {}

    /** Returns the stored path. */
    public function store(SettingKey $key, ImageUpload $upload): string
    {
        $image = $key === SettingKey::Favicon
            ? $this->images->thumbnail($upload->contents, 128)
            : $this->images->resize($upload->contents, 600, 240);

        return $this->files->store(self::DIRECTORY, $this->images->toWebp($image), 'webp');
    }

    public function delete(string $path): void
    {
        $this->files->delete($path);
    }
}
