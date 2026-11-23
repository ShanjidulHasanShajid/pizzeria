<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Models;

use App\Modules\Content\Domain\Enums\ContentType;

/**
 * Which model belongs to which content type.
 */
final class ContentModels
{
    public static function for(ContentType $type): ContentModel
    {
        return match ($type) {
            ContentType::HeroSlide => new HeroSlide,
            ContentType::Banner => new Banner,
            ContentType::TrustBadge => new TrustBadge,
            ContentType::GalleryImage => new GalleryImage,
        };
    }
}
