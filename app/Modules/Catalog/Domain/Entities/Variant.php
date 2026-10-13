<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Entities;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * One size or portion of a product with its own price: "Large", "6 pcs", "1.5 L".
 * Part of the Product aggregate. Product makes sure that exactly one variant is the default
 * and that labels are unique, so always change variants through Product (next batch).
 */
final class Variant
{
    public const LABEL_MAX_LENGTH = 60;

    private function __construct(
        private ?int $id,
        private string $label,
        private Money $price,
        private ?int $sizeTierId,
        private bool $isDefault,
        private bool $isAvailable,
        private SortOrder $sort,
    ) {}

    /**
     * A new, unsaved variant. It starts available and not default.
     */
    public static function create(string $label, Money $price, SortOrder $sort, ?int $sizeTierId = null): self
    {
        return new self(null, self::cleanLabel($label), $price, self::validSizeTierId($sizeTierId), false, true, $sort);
    }

    public static function reconstitute(
        int $id,
        string $label,
        Money $price,
        ?int $sizeTierId,
        bool $isDefault,
        bool $isAvailable,
        SortOrder $sort,
    ): self {
        return new self($id, self::cleanLabel($label), $price, self::validSizeTierId($sizeTierId), $isDefault, $isAvailable, $sort);
    }

    public function relabel(string $label): void
    {
        $this->label = self::cleanLabel($label);
    }

    public function changePrice(Money $price): void
    {
        $this->price = $price;
    }

    /**
     * Pass null when the variant is not linked to a size tier (for example "6 pcs").
     */
    public function linkToSizeTier(?int $sizeTierId): void
    {
        $this->sizeTierId = self::validSizeTierId($sizeTierId);
    }

    public function markAvailable(): void
    {
        $this->isAvailable = true;
    }

    public function markSoldOut(): void
    {
        $this->isAvailable = false;
    }

    public function moveTo(SortOrder $sort): void
    {
        $this->sort = $sort;
    }

    /**
     * Only Product calls this, right after clearing the old default.
     */
    public function markAsDefault(): void
    {
        $this->isDefault = true;
    }

    /**
     * Only Product calls this.
     */
    public function unmarkAsDefault(): void
    {
        $this->isDefault = false;
    }

    /**
     * Labels are compared without case and outer spaces: "Large" and " large" are the same label.
     */
    public function hasLabel(string $label): bool
    {
        return mb_strtolower($this->label) === mb_strtolower(trim($label));
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function sizeTierId(): ?int
    {
        return $this->sizeTierId;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function sort(): SortOrder
    {
        return $this->sort;
    }

    private static function cleanLabel(string $label): string
    {
        $label = trim($label);

        if ($label === '' || mb_strlen($label) > self::LABEL_MAX_LENGTH) {
            throw InvalidValue::because('A variant label must be 1 to 60 characters.');
        }

        return $label;
    }

    private static function validSizeTierId(?int $sizeTierId): ?int
    {
        if ($sizeTierId !== null && $sizeTierId < 1) {
            throw InvalidValue::because('Choose a valid size.');
        }

        return $sizeTierId;
    }
}
