<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\Services;

use App\Modules\Content\Application\DTOs\ImageUpload;
use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Shared\Application\Ports\FileStorage;
use App\Modules\Shared\Application\Ports\ImageProcessor;
use Throwable;

/**
 * Stores the images of content items as WebP, made to fit the place where they are shown.
 */
final class ContentImages
{
    public function __construct(
        private readonly FileStorage $files,
        private readonly ImageProcessor $images,
    ) {}

    /**
     * All or nothing: if one image fails, the ones stored before it are deleted again.
     *
     * @param  array<string, ImageUpload>  $uploads  field name => image
     * @return array<string, string> field name => stored path
     */
    public function storeAll(ContentType $type, array $uploads): array
    {
        $paths = [];

        try {
            foreach ($uploads as $field => $upload) {
                [$width, $height] = $this->box($type, $field);
                $image = $this->images->toWebp($this->images->resize($upload->contents, $width, $height));
                $paths[$field] = $this->files->store("content/{$type->value}", $image, 'webp');
            }
        } catch (Throwable $failure) {
            $this->deleteAll(array_values($paths));

            throw $failure;
        }

        return $paths;
    }

    /**
     * @param  list<string>  $paths
     */
    public function deleteAll(array $paths): void
    {
        foreach ($paths as $path) {
            $this->files->delete($path);
        }
    }

    /**
     * @return array{int, int} the largest width and height
     */
    private function box(ContentType $type, string $field): array
    {
        return match ($type) {
            ContentType::HeroSlide => $field === 'mobile_image' ? [900, 1200] : [1920, 1080],
            ContentType::Banner => [1200, 800],
            ContentType::TrustBadge, ContentType::GalleryImage => [1400, 1400],
        };
    }
}
