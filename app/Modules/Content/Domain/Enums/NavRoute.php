<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\Enums;

/**
 * The whitelist of pages the navbar may link to, in their default order.
 * The admin changes label, order, visibility and highlight, but never adds an address.
 */
enum NavRoute: string
{
    case Home = 'home';
    case Menu = 'menu';
    case Builder = 'builder';
    case GiftBoxes = 'gift-boxes';
    case OurStory = 'our-story';
    case Locations = 'locations';
    case Contact = 'contact';
    case MealDeals = 'meal-deals';
    case Faq = 'faq';
    case Catering = 'catering';
    case TrackOrder = 'track-order';

    public function defaultLabel(): string
    {
        return match ($this) {
            self::Home => 'Home',
            self::Menu => 'Menu',
            self::Builder => 'Customized Pizza',
            self::GiftBoxes => 'Gift Box',
            self::OurStory => 'Our Story',
            self::Locations => 'Location',
            self::Contact => 'Contact Us',
            self::MealDeals => 'Meal Deals',
            self::Faq => 'FAQ',
            self::Catering => 'Catering',
            self::TrackOrder => 'Track Order',
        };
    }

    /** The seven items of the website plan; the others wait until the admin switches them on. */
    public function isVisibleByDefault(): bool
    {
        return match ($this) {
            self::MealDeals, self::Faq, self::Catering, self::TrackOrder => false,
            default => true,
        };
    }

    public function isHighlightedByDefault(): bool
    {
        return $this === self::Builder;
    }
}
