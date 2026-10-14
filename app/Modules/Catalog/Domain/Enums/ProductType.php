<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Enums;

/**
 * What kind of product this is (docs/business-rules.md section 1).
 * The type decides which variant rules the Product aggregate checks.
 */
enum ProductType: string
{
    case Standard = 'standard';
    case Builder = 'builder';
    case Combo = 'combo';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard item',
            self::Builder => 'Build your own',
            self::Combo => 'Combo / meal deal',
        };
    }

    /** A combo has exactly one variant: the bundle price. */
    public function allowsOnlyOneVariant(): bool
    {
        return $this === self::Combo;
    }

    /** A builder has one variant per size tier: the base price of each size. */
    public function needsSizeTierOnEveryVariant(): bool
    {
        return $this === self::Builder;
    }

    /** Only a combo can be a gift box (decision 8). */
    public function canBeGiftable(): bool
    {
        return $this === self::Combo;
    }
}
