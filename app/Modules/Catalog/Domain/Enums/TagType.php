<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Enums;

/**
 * Dietary (Veg, Halal), allergen (Contains Nuts) or flavour (Spicy).
 */
enum TagType: string
{
    case Dietary = 'dietary';
    case Allergen = 'allergen';
    case Flavour = 'flavour';

    public function label(): string
    {
        return match ($this) {
            self::Dietary => 'Dietary',
            self::Allergen => 'Allergen',
            self::Flavour => 'Flavour',
        };
    }
}
