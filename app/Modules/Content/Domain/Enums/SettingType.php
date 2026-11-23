<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\Enums;

/**
 * What kind of value a setting holds. It decides how the value is checked and which form field is shown.
 */
enum SettingType: string
{
    case Text = 'text';
    case LongText = 'long_text';
    case Phone = 'phone';
    case Email = 'email';
    case Url = 'url';
    case Integer = 'integer';
    case Percentage = 'percentage';
    case Boolean = 'boolean';
    case Image = 'image';
}
