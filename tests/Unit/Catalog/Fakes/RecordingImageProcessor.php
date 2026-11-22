<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Fakes;

use App\Modules\Shared\Application\Ports\ImageProcessor;

/**
 * An ImageProcessor that does not touch pixels. It wraps the input in a label, so a test
 * can see what was done: toWebp(resize("photo")) gives "webp(resize-1600x1600(photo))".
 */
final class RecordingImageProcessor implements ImageProcessor
{
    public function resize(string $imageData, int $maxWidth, int $maxHeight): string
    {
        return "resize-{$maxWidth}x{$maxHeight}({$imageData})";
    }

    public function thumbnail(string $imageData, int $size): string
    {
        return "thumbnail-{$size}({$imageData})";
    }

    public function toWebp(string $imageData, int $quality = 80): string
    {
        return "webp({$imageData})";
    }
}
