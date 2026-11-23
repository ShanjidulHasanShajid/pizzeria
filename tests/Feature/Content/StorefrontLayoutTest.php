<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Content\Domain\Enums\NavRoute;
use App\Modules\Content\Domain\Repositories\LocationRepository;
use App\Modules\Content\Domain\Repositories\NavItemRepository;
use App\Modules\Content\Domain\Repositories\SettingsRepository;
use App\Modules\Content\Domain\ValueObjects\Location;
use App\Modules\Content\Domain\ValueObjects\LocationDetails;
use App\Modules\Content\Domain\ValueObjects\OpeningHours;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

beforeEach(function (): void {
    $this->saveSettings = fn (array $values) => app(SettingsRepository::class)->save($values);
    $this->saveBranch = fn (string $name, bool $isActive = true): int => app(LocationRepository::class)->save(Location::create(
        LocationDetails::fromInput($name, 'Road 27', 'Dhaka', '01712 345678', '', '', '', true),
        OpeningHours::fromWeek(['friday' => ['15:00', '23:30']]),
        $isActive,
        SortOrder::first(),
    ));
    // The navbar for big screens, without the drawer and the rest of the page.
    $this->navbar = fn (string $uri = '/faq'): string => (string) preg_replace('#^.*<nav [^>]*aria-label="Main">(.*?)</nav>.*$#s', '$1', (string) $this->get($uri)->getContent());
});

it('shows the settings around the page', function (): void {
    ($this->saveSettings)([
        'site_name' => 'Dhaka Slice', 'delivery_notice' => 'Delivering in Uttara', 'phone' => '+880 1712-345678',
        'email' => 'hello@example.com', 'address_line' => 'Gulshan Avenue', 'about_text' => 'We love dough.',
        'facebook_url' => 'https://www.facebook.com/slice',
    ]);

    $this->get('/faq')->assertOk()
        ->assertSee('<title>FAQ | Dhaka Slice</title>', false)
        ->assertSee('Delivering in Uttara')
        ->assertSee('href="tel:+8801712345678"', false)
        ->assertSee('mailto:hello@example.com', false)
        ->assertSee('Gulshan Avenue')
        ->assertSee('We love dough.')
        ->assertSee('https://www.facebook.com/slice')
        ->assertDontSee('Instagram');
});

it('leaves out what is empty', function (): void {
    ($this->saveSettings)(['about_text' => '', 'delivery_notice' => '']);

    $this->get('/faq')->assertOk()
        ->assertDontSee('tel:', false)
        ->assertDontSee('mailto:', false)
        ->assertDontSee('Follow us')
        ->assertDontSee('Our branches')
        ->assertDontSee('Show menu categories');
});

it('never prints typed text as HTML', function (): void {
    ($this->saveSettings)(['site_name' => '<b>Slice</b>', 'delivery_notice' => '<script>alert(1)</script>']);

    $this->get('/faq')->assertOk()
        ->assertDontSee('<b>Slice</b>', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;b&gt;Slice', false);
});

it('shows the uploaded logo and browser icon, or the built-in ones', function (): void {
    $this->get('/faq')->assertSee('logo-mark.svg')->assertSee('favicon.svg');

    ($this->saveSettings)(['site_name' => 'Dhaka Slice', 'logo' => 'settings/logo.webp', 'favicon' => 'settings/icon.webp']);

    $this->get('/faq')
        ->assertSee('settings/logo.webp')
        ->assertSee('alt="Dhaka Slice"', false)
        ->assertSee('settings/icon.webp')
        ->assertDontSee('logo-mark.svg')
        ->assertDontSee('favicon.svg');
});

it('shows the navbar as the admin arranged it', function (): void {
    $items = app(NavItemRepository::class);
    $items->save($items->get(NavRoute::Contact)->rename('Write to us')->movedTo(SortOrder::fromInt(1)));
    $items->save($items->get(NavRoute::GiftBoxes)->withVisibilityToggled());
    $items->save($items->get(NavRoute::Catering)->withVisibilityToggled()->withHighlightToggled());
    $items->save($items->get(NavRoute::Builder)->withHighlightToggled());

    $navbar = ($this->navbar)('/contact');
    $position = fn (string $label): int|false => strpos($navbar, $label);

    expect($position('Write to us'))->toBeLessThan($position('Home'))
        ->and($position('Gift Box'))->toBeFalse()
        ->and($position('Catering'))->not->toBeFalse()
        ->and(substr_count($navbar, 'bg-brand-600'))->toBe(1)
        ->and(substr_count($navbar, 'aria-current="page"'))->toBe(1);
});

it('marks the menu as active on category pages', function (): void {
    expect(($this->navbar)('/menu/pizza'))->toMatch('#aria-current="page"[^>]*>Menu<#');
});

it('shows the category tree of the catalog in the mega menu', function (): void {
    $drinks = CategoryModel::factory()->create(['name' => 'Cold Drinks', 'slug' => 'cold-drinks']);
    CategoryModel::factory()->childOf($drinks)->create(['name' => 'Iced Coffee', 'slug' => 'iced-coffee']);
    CategoryModel::factory()->inactive()->create(['name' => 'Secret Menu']);
    CategoryModel::factory()->hiddenFromMegaMenu()->create(['name' => 'Hidden Deals']);

    $navbar = ($this->navbar)();

    expect($navbar)->toContain('Show menu categories')
        ->toContain('/menu/cold-drinks')
        ->toContain('Iced Coffee')
        ->not->toContain('Secret Menu')
        ->not->toContain('Hidden Deals');
});

it('shows the active branches with their hours in the footer', function (): void {
    ($this->saveBranch)('Dhanmondi');
    ($this->saveBranch)('Not yet open', false);

    $this->get('/faq')
        ->assertSee('Our branches')
        ->assertSee('Dhanmondi')
        ->assertSee('Road 27, Dhaka')
        ->assertSee('Saturday to Thursday')
        ->assertSee('3:00 PM to 11:30 PM')
        ->assertDontSee('Not yet open');
});

it('shows a change of the admin on the next page', function (): void {
    $this->get('/faq')->assertDontSee('Banani');
    $id = ($this->saveBranch)('Banani');
    $this->get('/faq')->assertSee('Banani');
    app(LocationRepository::class)->delete($id);
    $this->get('/faq')->assertDontSee('Banani');
});
