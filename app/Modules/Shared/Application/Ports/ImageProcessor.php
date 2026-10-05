<?php

declare(strict_types=1);

namespace App\Modules\Shared\Application\Ports;

/**
 * Works on image bytes (a string) and returns image bytes. No files, no framework.
 */
interface ImageProcessor
{
    /**
     * Makes the image fit inside the box, keeping its shape. Never makes it bigger.
     */
    public function resize(string $imageData, int $maxWidth, int $maxHeight): string;

    /**
     * Makes a square picture of the given size, cutting the edges if needed.
     */
    public function thumbnail(string $imageData, int $size): string;

    public function toWebp(string $imageData, int $quality = 80): string;
}
