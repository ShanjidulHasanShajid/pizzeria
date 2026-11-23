<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\ValueObjects;

use App\Modules\Content\Domain\Enums\FieldKind;

/**
 * One field of a content type. The name is also the column in its table.
 */
final readonly class ContentField
{
    public const LINK_MAX_LENGTH = 255;

    private function __construct(
        public string $name,
        public FieldKind $kind,
        public bool $isRequired,
        public int $maxLength,
    ) {}

    public static function text(string $name, int $maxLength, bool $isRequired = false): self
    {
        return new self($name, FieldKind::Text, $isRequired, $maxLength);
    }

    public static function link(string $name): self
    {
        return new self($name, FieldKind::Link, false, self::LINK_MAX_LENGTH);
    }

    public static function icon(string $name): self
    {
        return new self($name, FieldKind::Icon, true, 50);
    }

    public static function image(string $name, bool $isRequired = false): self
    {
        return new self($name, FieldKind::Image, $isRequired, 255);
    }

    public function isImage(): bool
    {
        return $this->kind === FieldKind::Image;
    }

    /**
     * Why this (trimmed) value does not fit, or null when it is fine.
     */
    public function problemWith(string $value): ?string
    {
        if ($value === '') {
            return $this->isRequired ? 'This field is required.' : null;
        }

        if (mb_strlen($value) > $this->maxLength) {
            return "Use at most {$this->maxLength} characters.";
        }

        return match ($this->kind) {
            FieldKind::Link => self::isSafeLink($value) ? null : 'Enter a page of this site such as /menu, or a full address that starts with https://',
            FieldKind::Icon => preg_match('/^[a-z0-9-]+$/', $value) === 1 ? null : 'Choose an icon from the list.',
            FieldKind::Text, FieldKind::Image => null,
        };
    }

    /** Never "javascript:...", never "//other-site", never a backslash trick. */
    private static function isSafeLink(string $value): bool
    {
        if (preg_match('#^/(?![/\\\\])[^\s\\\\]*$#', $value) === 1) {
            return true;
        }

        return str_starts_with($value, 'https://') && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}
