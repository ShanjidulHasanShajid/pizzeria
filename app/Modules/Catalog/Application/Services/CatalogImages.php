<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Services;

use App\Modules\Catalog\Application\DTOs\ImageUpload;
use App\Modules\Catalog\Domain\ValueObjects\ImagePath;
use App\Modules\Shared\Application\Ports\FileStorage;
use App\Modules\Shared\Application\Ports\ImageProcessor;
use Throwable;

/**
 * Turns an uploaded image into the two files the catalog keeps, and deletes them again.
 * Used by the category use cases now and by the product use cases in batch 3.
 *
 *   original:  at most 1600 x 1600, shape kept, WebP   e.g. categories/9f1c....webp
 *   thumbnail: 400 x 400 square, WebP                  e.g. categories/thumbs/3a7e....webp
 */
final class CatalogImages
{
    public const MAX_SIZE = 1600;

    public const THUMBNAIL_SIZE = 400;

    public function __construct(
        private readonly FileStorage $files,
        private readonly ImageProcessor $images,
    ) {}

    public function store(ImageUpload $upload, string $directory): ImagePath
    {
        $original = $this->images->toWebp($this->images->resize($upload->contents, self::MAX_SIZE, self::MAX_SIZE));
        $thumbnail = $this->images->toWebp($this->images->thumbnail($upload->contents, self::THUMBNAIL_SIZE));

        $originalPath = $this->files->store($directory, $original, 'webp');

        try {
            $thumbnailPath = $this->files->store($directory.'/thumbs', $thumbnail, 'webp');
        } catch (Throwable $failure) {
            $this->files->delete($originalPath); // never leave half a pair behind

            throw $failure;
        }

        return ImagePath::of($originalPath, $thumbnailPath);
    }

    public function delete(ImagePath $path): void
    {
        $this->files->delete($path->original());

        if ($path->hasThumbnail()) {
            $this->files->delete($path->thumbnail());
        }
    }
}
