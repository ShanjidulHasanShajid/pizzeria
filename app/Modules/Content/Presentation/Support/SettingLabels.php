<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Support;

use App\Modules\Content\Domain\Enums\SettingKey;

/**
 * What the settings screen calls each setting, and a hint below the field.
 */
final class SettingLabels
{
    /**
     * @return array{label: string, help: ?string}
     */
    public static function for(SettingKey $key): array
    {
        [$label, $help] = match ($key) {
            SettingKey::SiteName => ['Shop name', null],
            SettingKey::Logo => ['Logo', 'JPG, PNG or WebP, at least 100 pixels wide, at most 2 MB. Without a logo the built-in one is shown.'],
            SettingKey::Favicon => ['Browser icon', 'Square JPG, PNG or WebP, at least 64 x 64 pixels.'],
            SettingKey::Phone => ['Phone', 'For example 01712 345678 or a hotline number.'],
            SettingKey::Email => ['Email', null],
            SettingKey::AddressLine => ['Address line', 'One line for the header and footer.'],
            SettingKey::FacebookUrl => ['Facebook', 'The full address, starting with https://'],
            SettingKey::InstagramUrl => ['Instagram', 'The full address, starting with https://'],
            SettingKey::YoutubeUrl => ['YouTube', 'The full address, starting with https://'],
            SettingKey::VatPercentage => ['VAT in percent', 'For example 5 or 7.5. Enter 0 for no VAT.'],
            SettingKey::PricesIncludeVat => ['Menu prices include VAT', null],
            SettingKey::StoreOpen => ['The shop accepts orders', null],
            SettingKey::HotItemsLimit => ['Hot items on the home page', 'A number from 1 to 24.'],
            SettingKey::BuilderTitle => ['Pizza builder: title', null],
            SettingKey::BuilderText => ['Pizza builder: text', null],
            SettingKey::CombosStripTitle => ['Meal deals: title', null],
            SettingKey::OpeningHoursText => ['Opening hours text', 'For the strip on the home page. Leave empty to hide it.'],
            SettingKey::DeliveryNotice => ['Delivery notice', 'For the bar at the very top. Leave empty to hide it.'],
        };

        return ['label' => $label, 'help' => $help];
    }
}
