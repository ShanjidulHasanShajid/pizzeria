<?php

declare(strict_types=1);

namespace App\Modules\Customization\Presentation\Support;

use App\Modules\Customization\Application\DTOs\ProductOptionGroupView;

/**
 * The rules of a group in words: "Required, choose 1", "Choose up to 5, the first 2 are free".
 */
final class OptionGroupHint
{
    public static function for(ProductOptionGroupView $group): string
    {
        $parts = [];

        if ($group->isRequired) {
            $parts[] = 'Required';
        }

        $parts[] = match (true) {
            $group->minimum === $group->maximum => "choose {$group->maximum}",
            $group->minimum > 1 || ($group->isRequired && $group->minimum === 1) => "choose {$group->minimum} to {$group->maximum}",
            default => "choose up to {$group->maximum}",
        };

        if ($group->freeCount > 0) {
            $parts[] = $group->freeCount === 1 ? '1 is free' : "{$group->freeCount} are free";
        }

        return ucfirst(implode(', ', $parts));
    }
}
