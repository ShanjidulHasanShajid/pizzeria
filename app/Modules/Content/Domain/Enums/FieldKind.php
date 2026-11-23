<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\Enums;

/**
 * What a field of a content item holds.
 */
enum FieldKind: string
{
    case Text = 'text';
    case Link = 'link';
    case Icon = 'icon';
    case Image = 'image';
}
