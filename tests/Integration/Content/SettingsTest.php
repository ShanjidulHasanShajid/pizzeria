<?php

declare(strict_types=1);

use App\Modules\Content\Application\DTOs\SettingField;
use App\Modules\Content\Application\Queries\SettingsQuery;
use App\Modules\Content\Domain\Enums\SettingGroup;
use App\Modules\Content\Domain\Repositories\SettingsRepository;
use App\Modules\Content\Infrastructure\Persistence\Models\Setting as SettingModel;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedSettingsRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    Cache::forget(CachedSettingsRepository::CACHE_KEY);
    $this->settings = app(SettingsRepository::class);
    $this->query = app(SettingsQuery::class);
});

it('gives the defaults while nothing is saved', function (): void {
    $current = $this->query->current();

    expect($current->siteName)->toBe('Pizzeria')
        ->and($current->logoPath)->toBeNull()
        ->and($current->vatPercentage->isZero())->toBeTrue()
        ->and($current->pricesIncludeVat)->toBeTrue()
        ->and($current->isStoreOpen)->toBeTrue()
        ->and($current->hotItemsLimit)->toBe(8)
        ->and($current->deliveryNotice)->toBe('');
});

it('saves values, updates them in place and gives them with their real types', function (): void {
    $this->settings->save(['vat_percentage' => '5.00', 'store_open' => '0', 'logo' => 'settings/logo.webp']);
    $this->settings->save(['vat_percentage' => '7.50', 'hot_items_limit' => '12']);

    $current = $this->query->current();

    expect($current->vatPercentage->basisPoints())->toBe(750)
        ->and($current->isStoreOpen)->toBeFalse()
        ->and($current->logoPath)->toBe('settings/logo.webp')
        ->and($current->hotItemsLimit)->toBe(12);
    $this->assertDatabaseCount('settings', 4);
});

it('gives the fields of one tab with saved values and defaults', function (): void {
    $this->settings->save(['builder_title' => '']);

    $values = [];

    foreach ($this->query->fields(SettingGroup::Home) as $field) {
        /** @var SettingField $field */
        $values[$field->key->value] = $field->value;
    }

    expect($values['builder_title'])->toBe('') // emptied on purpose, so not the default
        ->and($values['combos_strip_title'])->toBe('Meal deals')
        ->and($values)->toHaveCount(5);
});

it('reads the database once and then the cache', function (): void {
    $this->settings->save(['site_name' => 'Dhaka Pizza']);
    $this->query->current();

    DB::enableQueryLog();
    $name = $this->query->current()->siteName;

    expect($name)->toBe('Dhaka Pizza')
        ->and(array_filter(DB::getQueryLog(), fn (array $query): bool => str_contains($query['query'], 'settings')))->toBe([]);
});

it('clears the cache when settings are saved', function (): void {
    $this->query->current();

    $this->settings->save(['site_name' => 'New name']);

    expect($this->query->current()->siteName)->toBe('New name');
});

it('keeps the cache when the save is rolled back', function (): void {
    $this->settings->save(['site_name' => 'Old name']);
    $this->query->current();

    try {
        DB::transaction(function (): void {
            $this->settings->save(['site_name' => 'Never saved']);

            throw new RuntimeException('Something else failed.');
        });
    } catch (RuntimeException) {
        // expected
    }

    expect($this->query->current()->siteName)->toBe('Old name')
        ->and(SettingModel::query()->where('key', 'site_name')->value('value'))->toBe('Old name');
});
