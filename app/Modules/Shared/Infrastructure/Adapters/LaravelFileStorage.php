<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Adapters;

use App\Modules\Shared\Application\Ports\FileStorage;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Str;
use RuntimeException;

final class LaravelFileStorage implements FileStorage
{
    public function __construct(
        private readonly Factory $filesystems,
        private readonly string $diskName = 'public',
    ) {}

    public function store(string $directory, string $contents, string $extension): string
    {
        $path = trim($directory, '/').'/'.Str::uuid()->toString().'.'.ltrim($extension, '.');

        if (! $this->disk()->put($path, $contents)) {
            throw new RuntimeException("The file could not be stored at {$path}.");
        }

        return $path;
    }

    public function delete(string $path): void
    {
        $this->disk()->delete($path);
    }

    public function url(string $path): string
    {
        return $this->disk()->url($path);
    }

    private function disk(): FilesystemAdapter
    {
        $disk = $this->filesystems->disk($this->diskName);

        if (! $disk instanceof FilesystemAdapter) {
            throw new RuntimeException("Disk [{$this->diskName}] is not a Laravel filesystem adapter.");
        }

        return $disk;
    }
}
