<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Queries;

use App\Modules\Content\Application\DTOs\SettingField;
use App\Modules\Content\Application\DTOs\SiteSettings;
use App\Modules\Content\Application\Queries\SettingsQuery;
use App\Modules\Content\Domain\Enums\SettingGroup;
use App\Modules\Content\Domain\Enums\SettingKey;
use App\Modules\Content\Domain\Repositories\SettingsRepository;
use App\Modules\Shared\Domain\ValueObjects\Percentage;

/**
 * Reads through the (cached) repository and fills in the defaults.
 */
final class RepositorySettingsQuery implements SettingsQuery
{
    public function __construct(private readonly SettingsRepository $settings) {}

    public function current(): SiteSettings
    {
        $saved = $this->settings->all();
        $value = fn (SettingKey $key): string => $saved[$key->value] ?? $key->default();
        $path = fn (SettingKey $key): ?string => $value($key) === '' ? null : $value($key);

        return new SiteSettings(
            siteName: $value(SettingKey::SiteName),
            logoPath: $path(SettingKey::Logo),
            faviconPath: $path(SettingKey::Favicon),
            phone: $value(SettingKey::Phone),
            email: $value(SettingKey::Email),
            addressLine: $value(SettingKey::AddressLine),
            facebookUrl: $value(SettingKey::FacebookUrl),
            instagramUrl: $value(SettingKey::InstagramUrl),
            youtubeUrl: $value(SettingKey::YoutubeUrl),
            vatPercentage: Percentage::fromString($value(SettingKey::VatPercentage)),
            pricesIncludeVat: $value(SettingKey::PricesIncludeVat) === '1',
            isStoreOpen: $value(SettingKey::StoreOpen) === '1',
            hotItemsLimit: (int) $value(SettingKey::HotItemsLimit),
            builderTitle: $value(SettingKey::BuilderTitle),
            builderText: $value(SettingKey::BuilderText),
            combosStripTitle: $value(SettingKey::CombosStripTitle),
            openingHoursText: $value(SettingKey::OpeningHoursText),
            deliveryNotice: $value(SettingKey::DeliveryNotice),
            aboutText: $value(SettingKey::AboutText),
        );
    }

    public function fields(SettingGroup $group): array
    {
        $saved = $this->settings->all();

        return array_map(
            fn (SettingKey $key): SettingField => new SettingField($key, $saved[$key->value] ?? $key->default()),
            $group->keys(),
        );
    }
}
