<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\ValueObjects;

/**
 * The "New" and "Hot" flags shown on a product card.
 * Changing a badge gives a new object; the old one never changes.
 */
final readonly class ProductBadges
{
    private function __construct(
        private bool $isNew,
        private bool $isHot,
    ) {}

    public static function none(): self
    {
        return new self(false, false);
    }

    public static function of(bool $isNew, bool $isHot): self
    {
        return new self($isNew, $isHot);
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }

    public function isHot(): bool
    {
        return $this->isHot;
    }

    public function withNew(bool $isNew): self
    {
        return new self($isNew, $this->isHot);
    }

    public function withHot(bool $isHot): self
    {
        return new self($this->isNew, $isHot);
    }

    public function hasAny(): bool
    {
        return $this->isNew || $this->isHot;
    }

    public function equals(self $other): bool
    {
        return $this->isNew === $other->isNew && $this->isHot === $other->isHot;
    }
}
