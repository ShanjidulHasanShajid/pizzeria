<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Support;

/**
 * The icons a tag may use: icon name => label in the form.
 * Every name must exist in resources/views/components/icon.blade.php.
 * To offer a new icon: add its path there, then add it here.
 */
final class TagIcons
{
    public const OPTIONS = [
        'fire' => 'Flame (spicy)',
        'shield-check' => 'Shield (halal)',
        'check-circle' => 'Check mark (veg)',
        'exclamation-triangle' => 'Warning (allergen)',
        'information-circle' => 'Info',
    ];
}
