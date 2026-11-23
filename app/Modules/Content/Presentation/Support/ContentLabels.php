<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Support;

/**
 * What the content screens call each field, and which icons a trust badge may use.
 * Every icon name must exist in resources/views/components/icon.blade.php.
 */
final class ContentLabels
{
    public const ICONS = [
        'shield-check' => 'Shield',
        'check-circle' => 'Check mark',
        'clock' => 'Clock',
        'fire' => 'Flame',
        'map-pin' => 'Map pin',
        'phone' => 'Phone',
        'shopping-bag' => 'Bag',
        'home' => 'House',
    ];

    /** field name => [label, help] */
    private const FIELDS = [
        'title' => ['Title', null],
        'subtitle' => ['Text below the title', null],
        'button_text' => ['Button text', 'Leave empty for no button.'],
        'button_link' => ['Button link', 'A page of this site such as /menu, or a full address with https://'],
        'link' => ['Link', 'A page of this site such as /menu, or a full address with https://'],
        'image' => ['Image', 'JPG, PNG or WebP, at least 400 pixels wide, at most 2 MB.'],
        'mobile_image' => ['Image for phones', 'Optional. Upright photos look best.'],
        'icon' => ['Icon', null],
        'text' => ['Text', null],
        'caption' => ['Caption', 'Also read out by screen readers.'],
    ];

    /**
     * @return array{label: string, help: ?string}
     */
    public static function for(string $field): array
    {
        [$label, $help] = self::FIELDS[$field] ?? [$field, null];

        return ['label' => $label, 'help' => $help];
    }
}
