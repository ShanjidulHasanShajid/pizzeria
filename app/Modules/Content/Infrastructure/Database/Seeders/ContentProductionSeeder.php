<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Database\Seeders;

use App\Modules\Content\Application\DTOs\SaveContentItemInput;
use App\Modules\Content\Application\DTOs\SaveLocationInput;
use App\Modules\Content\Application\UseCases\Items\CreateContentItem;
use App\Modules\Content\Application\UseCases\Locations\CreateLocation;
use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Domain\Enums\Weekday;
use App\Modules\Content\Infrastructure\Persistence\Models\Location;
use App\Modules\Content\Infrastructure\Persistence\Models\TrustBadge;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedContentItemRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedHomeLayoutRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedLocationRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedNavItemRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedSettingsRepository;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Seeder;

/**
 * Trust badges and one sample location. The location is switched off until the admin has
 * entered the real address. Settings and navigation need no rows: they have defaults.
 * Safe to run again: a list that was ever filled (also in the Trash) is left alone.
 */
final class ContentProductionSeeder extends Seeder
{
    private const BADGES = [
        ['icon' => 'fire', 'title' => 'Freshly baked', 'text' => 'Made to order'],
        ['icon' => 'clock', 'title' => 'Fast delivery', 'text' => 'Hot at your door'],
        ['icon' => 'shield-check', 'title' => 'Easy payment', 'text' => 'Cash on delivery'],
        ['icon' => 'check-circle', 'title' => 'Good ingredients', 'text' => 'Chosen with care'],
    ];

    public function __construct(
        private readonly CreateContentItem $createItem,
        private readonly CreateLocation $createLocation,
        private readonly Cache $cache,
    ) {}

    public function run(): void
    {
        $this->clearCache();

        if (TrustBadge::withTrashed()->doesntExist()) {
            foreach (self::BADGES as $badge) {
                $this->createItem->handle(new SaveContentItemInput(ContentType::TrustBadge, null, $badge, [], [], null, null, true));
            }
        }

        if (Location::withTrashed()->doesntExist()) {
            $hours = array_fill_keys(array_map(fn (Weekday $day): string => $day->value, Weekday::cases()), ['11:00', '23:00']);

            $this->createLocation->handle(new SaveLocationInput(
                null, 'Sample branch', 'Enter the address of your branch', '', '01700 000000', '', '', '', $hours, true, false,
            ));
        }
    }

    /** After "migrate:fresh" a cache outside the database still holds the old content. */
    private function clearCache(): void
    {
        foreach (ContentType::cases() as $type) {
            $this->cache->forget(CachedContentItemRepository::cacheKey($type));
        }

        $this->cache->forget(CachedSettingsRepository::CACHE_KEY);
        $this->cache->forget(CachedNavItemRepository::CACHE_KEY);
        $this->cache->forget(CachedLocationRepository::CACHE_KEY);
        $this->cache->forget(CachedHomeLayoutRepository::CACHE_KEY);
    }
}
