<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\Enums;

/**
 * The settings registry: every setting the shop has, with its group, type and default.
 * The value is the key in the database. A setting that was never saved has its default.
 */
enum SettingKey: string
{
    case SiteName = 'site_name';
    case Logo = 'logo';
    case Favicon = 'favicon';
    case AboutText = 'about_text';

    case Phone = 'phone';
    case Email = 'email';
    case AddressLine = 'address_line';

    case FacebookUrl = 'facebook_url';
    case InstagramUrl = 'instagram_url';
    case YoutubeUrl = 'youtube_url';

    case VatPercentage = 'vat_percentage';
    case PricesIncludeVat = 'prices_include_vat';
    case StoreOpen = 'store_open';

    case HotItemsLimit = 'hot_items_limit';
    case BuilderTitle = 'builder_title';
    case BuilderText = 'builder_text';
    case CombosStripTitle = 'combos_strip_title';
    case OpeningHoursText = 'opening_hours_text';

    case DeliveryNotice = 'delivery_notice';

    public function group(): SettingGroup
    {
        return match ($this) {
            self::SiteName, self::Logo, self::Favicon, self::AboutText => SettingGroup::General,
            self::Phone, self::Email, self::AddressLine => SettingGroup::Contact,
            self::FacebookUrl, self::InstagramUrl, self::YoutubeUrl => SettingGroup::Social,
            self::VatPercentage, self::PricesIncludeVat, self::StoreOpen => SettingGroup::Commerce,
            self::HotItemsLimit, self::BuilderTitle, self::BuilderText,
            self::CombosStripTitle, self::OpeningHoursText => SettingGroup::Home,
            self::DeliveryNotice => SettingGroup::Delivery,
        };
    }

    public function type(): SettingType
    {
        return match ($this) {
            self::Logo, self::Favicon => SettingType::Image,
            self::Phone => SettingType::Phone,
            self::Email => SettingType::Email,
            self::FacebookUrl, self::InstagramUrl, self::YoutubeUrl => SettingType::Url,
            self::VatPercentage => SettingType::Percentage,
            self::PricesIncludeVat, self::StoreOpen => SettingType::Boolean,
            self::HotItemsLimit => SettingType::Integer,
            self::BuilderText, self::AboutText => SettingType::LongText,
            self::SiteName, self::AddressLine, self::BuilderTitle,
            self::CombosStripTitle, self::OpeningHoursText, self::DeliveryNotice => SettingType::Text,
        };
    }

    /** An empty text means "show nothing". */
    public function default(): string
    {
        return match ($this) {
            self::SiteName => 'Pizzeria',
            // Open business decision 1: VAT is 0 until the owner enters the real rate.
            self::VatPercentage => '0.00',
            self::PricesIncludeVat, self::StoreOpen => '1',
            self::HotItemsLimit => '8',
            self::BuilderTitle => 'Build your own pizza',
            self::BuilderText => 'Choose the size, crust, sauce and toppings, and see the price as you go.',
            self::CombosStripTitle => 'Meal deals',
            self::AboutText => 'Hand-stretched pizza, fresh pasta and warm desserts, made to order in Dhaka and delivered hot to your door.',
            default => '',
        };
    }

    public function isRequired(): bool
    {
        return match ($this) {
            self::SiteName, self::VatPercentage, self::HotItemsLimit => true,
            default => false,
        };
    }
}
