<?php

declare(strict_types=1);

use App\Modules\Content\Application\Queries\SettingsQuery;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedSettingsRepository;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    Cache::forget(CachedSettingsRepository::CACHE_KEY);
    $this->actingAs(User::factory()->admin()->create());
    $this->current = fn () => app(SettingsQuery::class)->current();
});

it('shows every tab with its fields, and the first one for an unknown tab', function (string $tab, string $label): void {
    $this->get("/admin/settings?tab={$tab}")->assertOk()->assertSee($label);
})->with([
    ['general', 'Shop name'],
    ['contact', 'Address line'],
    ['social', 'Instagram'],
    ['commerce', 'VAT in percent'],
    ['home', 'Hot items on the home page'],
    ['delivery', 'Delivery notice'],
    ['nonsense', 'Shop name'],
]);

it('saves a tab and goes back to it', function (): void {
    $this->put('/admin/settings/commerce', ['vat_percentage' => '7.5', 'prices_include_vat' => '0', 'store_open' => '1'])
        ->assertRedirect('/admin/settings?tab=commerce')
        ->assertSessionHas('success');

    expect(($this->current)()->vatPercentage->toString())->toBe('7.50')
        ->and(($this->current)()->pricesIncludeVat)->toBeFalse()
        ->and(($this->current)()->isStoreOpen)->toBeTrue();
    $this->get('/admin/settings?tab=commerce')->assertSee('value="7.50"', false);
});

it('lets a text be emptied', function (): void {
    $this->put('/admin/settings/home', ['hot_items_limit' => '6', 'builder_title' => '', 'builder_text' => '', 'combos_strip_title' => '', 'opening_hours_text' => ''])
        ->assertSessionHasNoErrors();

    expect(($this->current)()->builderTitle)->toBe('')
        ->and(($this->current)()->hotItemsLimit)->toBe(6);
});

it('shows every error under its field and saves nothing', function (): void {
    $this->put('/admin/settings/contact', ['phone' => 'call me', 'email' => 'nope', 'address_line' => 'Banani, Dhaka'])
        ->assertSessionHasErrors(['phone', 'email']);
    $this->put('/admin/settings/social', ['facebook_url' => 'javascript:alert(1)'])->assertSessionHasErrors('facebook_url');
    $this->put('/admin/settings/general', ['site_name' => ''])->assertSessionHasErrors('site_name');

    $this->assertDatabaseCount('settings', 0);
});

it('uploads a logo and a browser icon, and removes the logo again', function (): void {
    $this->put('/admin/settings/general', [
        'site_name' => 'Dhaka Pizza',
        'logo' => UploadedFile::fake()->image('logo.png', 800, 300),
        'favicon' => UploadedFile::fake()->image('icon.png', 256, 256),
    ])->assertSessionHasNoErrors();

    $logo = ($this->current)()->logoPath;

    expect($logo)->toStartWith('settings/')->toEndWith('.webp');
    Storage::disk('public')->assertExists([$logo, ($this->current)()->faviconPath]);

    $this->put('/admin/settings/general', ['site_name' => 'Dhaka Pizza', 'remove_logo' => '1'])->assertSessionHasNoErrors();

    expect(($this->current)()->logoPath)->toBeNull()
        ->and(($this->current)()->faviconPath)->not->toBeNull();
    Storage::disk('public')->assertMissing($logo);
});

it('refuses a logo that is too small, an icon that is not square, and a file that is no image', function (): void {
    $this->put('/admin/settings/general', ['site_name' => 'X', 'logo' => UploadedFile::fake()->image('logo.png', 50, 50)])->assertSessionHasErrors('logo');
    $this->put('/admin/settings/general', ['site_name' => 'X', 'favicon' => UploadedFile::fake()->image('icon.png', 300, 100)])->assertSessionHasErrors('favicon');
    $this->put('/admin/settings/general', ['site_name' => 'X', 'logo' => UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml')])->assertSessionHasErrors('logo');

    $this->assertDatabaseCount('settings', 0);
});

it('ignores keys of another tab and answers 404 for an unknown tab', function (): void {
    $this->put('/admin/settings/delivery', ['delivery_notice' => 'Free delivery today', 'vat_percentage' => '99'])->assertSessionHasNoErrors();

    expect(($this->current)()->deliveryNotice)->toBe('Free delivery today')
        ->and(($this->current)()->vatPercentage->isZero())->toBeTrue();
    $this->put('/admin/settings/nonsense', [])->assertNotFound();
});

it('does not let staff see or change settings', function (): void {
    $this->actingAs(User::factory()->staff()->create());

    $this->get('/admin/settings')->assertForbidden();
    $this->put('/admin/settings/commerce', ['vat_percentage' => '1'])->assertForbidden();

    $this->assertDatabaseCount('settings', 0);
});
