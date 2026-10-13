<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;

/**
 * Where an image file is stored, relative to the public disk, for example
 * "products/0b6f2c.webp", plus an optional smaller thumbnail.
 * It only holds paths. Saving the file is the FileStorage port's job.
 */
final readonly class ImagePath
{
    public const MAX_LENGTH = 255;

    /**
     * Folders and a file name separated by single "/". No part may start with a dot,
     * so "..", hidden files and absolute paths are impossible. Image extensions only.
     */
    private const PATTERN = '#^[A-Za-z0-9_-][A-Za-z0-9._-]*(?:/[A-Za-z0-9_-][A-Za-z0-9._-]*)*\.(?:jpe?g|png|webp|avif)$#i';

    private function __construct(
        private string $original,
        private ?string $thumbnail,
    ) {
        self::check($original);

        if ($thumbnail !== null) {
            self::check($thumbnail);
        }
    }

    public static function of(string $original, ?string $thumbnail = null): self
    {
        return new self($original, $thumbnail);
    }

    public function original(): string
    {
        return $this->original;
    }

    /**
     * The thumbnail when there is one, otherwise the original.
     */
    public function thumbnail(): string
    {
        return $this->thumbnail ?? $this->original;
    }

    public function hasThumbnail(): bool
    {
        return $this->thumbnail !== null;
    }

    public function equals(self $other): bool
    {
        return $this->original === $other->original && $this->thumbnail === $other->thumbnail;
    }

    private static function check(string $path): void
    {
        if (strlen($path) > self::MAX_LENGTH || preg_match(self::PATTERN, $path) !== 1) {
            throw InvalidValue::because('An image path must be a relative path to a jpg, png, webp or avif file.');
        }
    }
}
