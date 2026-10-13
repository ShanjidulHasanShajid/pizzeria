<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Entities;

use App\Modules\Catalog\Domain\Enums\TagType;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * A label on products: Veg, Spicy, Halal, Contains Nuts ...
 * The icon is the name of an icon from the site's <x-icon> set, for example "fire".
 */
final class Tag
{
    public const NAME_MAX_LENGTH = 50;

    private const ICON_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private const ICON_MAX_LENGTH = 50;

    private function __construct(
        private ?int $id,
        private string $name,
        private Slug $slug,
        private TagType $type,
        private ?string $icon,
        private SortOrder $sort,
        private bool $isActive,
    ) {}

    public static function create(string $name, Slug $slug, TagType $type, SortOrder $sort, ?string $icon = null): self
    {
        return new self(null, self::cleanName($name), $slug, $type, self::cleanIcon($icon), $sort, true);
    }

    public static function reconstitute(
        int $id,
        string $name,
        Slug $slug,
        TagType $type,
        ?string $icon,
        SortOrder $sort,
        bool $isActive,
    ): self {
        return new self($id, self::cleanName($name), $slug, $type, self::cleanIcon($icon), $sort, $isActive);
    }

    public function rename(string $name): void
    {
        $this->name = self::cleanName($name);
    }

    public function changeSlug(Slug $slug): void
    {
        $this->slug = $slug;
    }

    public function changeType(TagType $type): void
    {
        $this->type = $type;
    }

    public function changeIcon(?string $icon): void
    {
        $this->icon = self::cleanIcon($icon);
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

    public function type(): TagType
    {
        return $this->type;
    }

    public function icon(): ?string
    {
        return $this->icon;
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
            throw InvalidValue::because('A tag name must be 1 to 50 characters.');
        }

        return $name;
    }

    /**
     * An empty icon means "no icon". Otherwise lower-case words joined by hyphens.
     */
    private static function cleanIcon(?string $icon): ?string
    {
        $icon = $icon === null ? '' : trim($icon);

        if ($icon === '') {
            return null;
        }

        if (strlen($icon) > self::ICON_MAX_LENGTH || preg_match(self::ICON_PATTERN, $icon) !== 1) {
            throw InvalidValue::because('An icon name may contain only lower-case letters, numbers and hyphens.');
        }

        return $icon;
    }
}
