<?php

declare(strict_types=1);

use App\Modules\Content\Domain\Enums\SettingGroup;
use App\Modules\Content\Domain\Enums\SettingKey;
use App\Modules\Content\Domain\Enums\SettingType;
use App\Modules\Content\Domain\Services\SettingValues;
use App\Modules\Content\Presentation\Support\SettingLabels;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;

it('puts every setting into exactly one group, and every group has settings', function (): void {
    $keys = array_merge(...array_map(fn (SettingGroup $group): array => $group->keys(), SettingGroup::cases()));

    expect($keys)->toHaveCount(count(SettingKey::cases()));

    foreach (SettingGroup::cases() as $group) {
        expect($group->keys())->not->toBe([])
            ->and($group->label())->not->toBe('');
    }
});

it('has a default that passes its own check, and a label, for every setting', function (): void {
    foreach (SettingKey::cases() as $key) {
        if ($key->type() !== SettingType::Image) {
            expect((new SettingValues)->clean($key, $key->default()))->toBe($key->default());
        }

        expect(SettingLabels::for($key)['label'])->not->toBe('');
    }
});

it('tidies valid values', function (SettingKey $key, string $typed, string $stored): void {
    expect((new SettingValues)->clean($key, $typed))->toBe($stored);
})->with([
    'text is trimmed' => [SettingKey::SiteName, '  Dhaka Pizza ', 'Dhaka Pizza'],
    'empty text' => [SettingKey::AddressLine, '   ', ''],
    'mobile' => [SettingKey::Phone, '+880 1712-345678', '+880 1712-345678'],
    'hotline' => [SettingKey::Phone, '16247', '16247'],
    'email' => [SettingKey::Email, ' Info@Example.com ', 'info@example.com'],
    'url' => [SettingKey::FacebookUrl, 'https://www.facebook.com/pizza', 'https://www.facebook.com/pizza'],
    'percentage' => [SettingKey::VatPercentage, '7.5', '7.50'],
    'integer' => [SettingKey::HotItemsLimit, '012', '12'],
    'switched on' => [SettingKey::StoreOpen, '1', '1'],
    'switched off' => [SettingKey::StoreOpen, '', '0'],
]);

it('refuses values that do not fit', function (SettingKey $key, string $typed): void {
    (new SettingValues)->clean($key, $typed);
})->throws(InvalidValue::class)->with([
    'empty shop name' => [SettingKey::SiteName, ' '],
    'too long text' => [SettingKey::SiteName, str_repeat('x', 151)],
    'too long text area' => [SettingKey::BuilderText, str_repeat('x', 501)],
    'letters in a phone' => [SettingKey::Phone, 'call me'],
    'not an email' => [SettingKey::Email, 'info@'],
    'http address' => [SettingKey::FacebookUrl, 'http://facebook.com/pizza'],
    'script address' => [SettingKey::FacebookUrl, 'javascript:alert(1)'],
    'percentage above 100' => [SettingKey::VatPercentage, '101'],
    'empty percentage' => [SettingKey::VatPercentage, ''],
    'zero hot items' => [SettingKey::HotItemsLimit, '0'],
    'too many hot items' => [SettingKey::HotItemsLimit, '25'],
    'not a switch' => [SettingKey::StoreOpen, 'maybe'],
    'typed image path' => [SettingKey::Logo, 'settings/logo.webp'],
]);
