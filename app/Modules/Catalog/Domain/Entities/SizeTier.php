<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Entities;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * A shared size (Personal, Medium, Large) used by pizzas and pies.
 * Option prices can depend on it (Phase 8).
 */
final class SizeTier
{
    public const NAME_MAX_LENGTH = 50;

    private function __construct(
        private ?int $id,
        private string $name,
        private Slug $slug,
        private SortOrder $sort,
        private bool $isActive,
    ) {}

    public static function create(string $name, Slug $slug, SortOrder $sort): self
    {
        return new self(null, self::cleanName($name), $slug, $sort, true);
    }

    public static function reconstitute(int $id, string $name, Slug $slug, SortOrder $sort, bool $isActive): self
    {
        return new self($id, self::cleanName($name), $slug, $sort, $isActive);
    }

    public function rename(string $name): void
    {
        $this->name = self::cleanName($name);
    }

    public function changeSlug(Slug $slug): void
    {
        $this->slug = $slug;
    }

    public function moveTo(SortOrder $sort): void
    {
        $this->sort = $sort;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): Slug
    {
        return $this->slug;
    }

    public function sort(): SortOrder
    {
        return $this->sort;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    private static function cleanName(string $name): string
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw InvalidValue::because('A size name must be 1 to 50 characters.');
        }

        return $name;
    }
}
