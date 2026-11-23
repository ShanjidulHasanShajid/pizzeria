<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Support;

use App\Modules\Content\Domain\Enums\NavRoute;

/**
 * Where each whitelisted navbar page leads, and on which pages its entry looks active.
 */
final class NavLinks
{
    public static function routeName(NavRoute $route): string
    {
        return match ($route) {
            NavRoute::Home => 'home',
            NavRoute::Menu => 'menu.index',
            NavRoute::Builder => 'builder.show',
            NavRoute::GiftBoxes => 'gift-boxes.index',
            NavRoute::OurStory => 'about',
            NavRoute::Locations => 'locations.index',
            NavRoute::Contact => 'contact',
            NavRoute::MealDeals => 'meal-deals.index',
            NavRoute::Faq => 'faq',
            NavRoute::Catering => 'catering',
            NavRoute::TrackOrder => 'orders.track',
        };
    }

    /**
     * @return list<string> patterns for request()->routeIs()
     */
    public static function activePatterns(NavRoute $route): array
    {
        return match ($route) {
            NavRoute::Menu => ['menu.*', 'categories.*', 'products.*'],
            NavRoute::Builder => ['builder.*'],
            NavRoute::GiftBoxes => ['gift-boxes.*'],
            NavRoute::Locations => ['locations.*'],
            NavRoute::MealDeals => ['meal-deals.*'],
            default => [self::routeName($route)],
        };
    }
}
