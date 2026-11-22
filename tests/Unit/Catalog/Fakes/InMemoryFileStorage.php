<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Fakes;

use App\Modules\Shared\Application\Ports\FileStorage;

/**
 * A FileStorage that keeps "files" in an array: path => contents. Paths are predictable
 * (categories/file-1.webp, categories/thumbs/file-2.webp ...), so tests can name them.
 */
final class InMemoryFileStorage implements FileStorage
{
    /** @var array<string, string> */
    private array $files = [];

    private int $counter = 0;

    public function store(string $directory, string $contents, string $extension): string
    {
        $path = trim($directory, '/').'/file-'.(++$this->counter).'.'.ltrim($extension, '.');
        $this->files[$path] = $contents;

        return $path;
    }

    public function delete(string $path): void
    {
        unset($this->files[$path]);
    }

    public function url(string $path): string
    {
        return '/storage/'.$path;
    }

    // Test helpers (not part of the interface)

    public function has(string $path): bool
    {
        return isset($this->files[$path]);
    }

    public function contents(string $path): ?string
    {
        return $this->files[$path] ?? null;
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        return array_keys($this->files);
    }
}
