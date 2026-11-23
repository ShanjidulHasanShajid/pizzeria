<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\Enums;

use App\Modules\Content\Domain\ValueObjects\ContentField;

/**
 * The lists of home page content. Each type says which fields it has and whether it is scheduled,
 * so one repository, one set of use cases and one admin screen serve all of them.
 * The value is also the address: /admin/hero-slides.
 */
enum ContentType: string
{
    case HeroSlide = 'hero-slides';
    case Banner = 'banners';
    case TrustBadge = 'trust-badges';
    case GalleryImage = 'gallery';

    public function label(): string
    {
        return match ($this) {
            self::HeroSlide => 'Hero slides',
            self::Banner => 'Banners',
            self::TrustBadge => 'Trust badges',
            self::GalleryImage => 'Gallery',
        };
    }

    public function singular(): string
    {
        return match ($this) {
            self::HeroSlide => 'Hero slide',
            self::Banner => 'Banner',
            self::TrustBadge => 'Trust badge',
            self::GalleryImage => 'Gallery image',
        };
    }

    /** Scheduled items show only inside their start and end date (business rules section 12). */
    public function isScheduled(): bool
    {
        return $this === self::HeroSlide || $this === self::Banner;
    }

    /**
     * @return list<ContentField>
     */
    public function fields(): array
    {
        return match ($this) {
            self::HeroSlide => [
                ContentField::text('title', 150, isRequired: true),
                ContentField::text('subtitle', 255),
                ContentField::text('button_text', 40),
                ContentField::link('button_link'),
                ContentField::image('image', isRequired: true),
                ContentField::image('mobile_image'),
            ],
            self::Banner => [
                ContentField::text('title', 150, isRequired: true),
                ContentField::link('link'),
                ContentField::image('image', isRequired: true),
            ],
            self::TrustBadge => [
                ContentField::icon('icon'),
                ContentField::text('title', 60, isRequired: true),
                ContentField::text('text', 120),
            ],
            self::GalleryImage => [
                ContentField::image('image', isRequired: true),
                ContentField::text('caption', 150),
            ],
        };
    }
}
