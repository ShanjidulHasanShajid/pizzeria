<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Adapters;

use App\Modules\Shared\Application\Ports\ImageProcessor;
use Intervention\Image\Format;
use Intervention\Image\Interfaces\ImageManagerInterface;

/**
 * Uses Intervention Image 4 (GD driver, set in config/intervention-image.php).
 */
final class InterventionImageProcessor implements ImageProcessor
{
    public function __construct(private readonly ImageManagerInterface $images) {}

    public function resize(string $imageData, int $maxWidth, int $maxHeight): string
    {
        return $this->images
            ->decodeBinary($imageData)
            ->scaleDown($maxWidth, $maxHeight)
            ->encode()
            ->toString();
    }

    public function thumbnail(string $imageData, int $size): string
    {
        return $this->images
            ->decodeBinary($imageData)
            ->cover($size, $size)
            ->encode()
            ->toString();
    }

    public function toWebp(string $imageData, int $quality = 80): string
    {
        return $this->images
            ->decodeBinary($imageData)
            ->encodeUsingFormat(Format::WEBP, quality: $quality)
            ->toString();
    }
}
