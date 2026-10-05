<?php

declare(strict_types=1);

namespace App\Modules\Shared\Application\Ports;

interface FileStorage
{
    /**
     * Saves the contents in the directory under a new unique name and returns the stored path,
     * for example "products/0b6f...-.webp". Keep that path in the database.
     */
    public function store(string $directory, string $contents, string $extension): string;

    public function delete(string $path): void;

    /**
     * The address a browser can use to show the file.
     */
    public function url(string $path): string;
}
