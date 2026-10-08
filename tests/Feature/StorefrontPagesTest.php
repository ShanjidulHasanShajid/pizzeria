<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Models\User;

it('serves every storefront page', function (string $uri): void {
    $this->get($uri)
        ->assertOk()
        ->assertSee('id="main"', false);
})->with([
    '/',
    '/menu',
    '/menu/pizza',
    '/products/margherita',
    '/build-your-pizza',
    '/gift-boxes',
    '/meal-deals',
    '/our-story',
    '/locations',
    '/contact',
    '/faq',
    '/catering',
    '/search',
    '/cart',
    '/checkout',
    '/order-success',
    '/track-order',
    '/terms',
    '/privacy',
    '/refund',
]);

it('serves the development component pages', function (string $uri): void {
    $this->get($uri)->assertOk();
})->with(['/ui-kit']);

it('serves the admin ui kit to staff', function (): void {
    $this->actingAs(User::factory()->staff()->create())
        ->get('/admin/ui-kit')
        ->assertOk();
});

it('serves the admin dashboard with the sidebar', function (): void {
    $this->actingAs(User::factory()->staff()->create())
        ->get('/admin')
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Admin sidebar');
});

it('shows the branded 404 page for an unknown address', function (): void {
    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertSee('Page not found');
});

it('shows each branded error page', function (int $code, string $title): void {
    $this->get("/_errors/{$code}")
        ->assertStatus($code)
        ->assertSee($title);
})->with([
    [403, 'Access denied'],
    [404, 'Page not found'],
    [419, 'Page expired'],
    [429, 'Too many requests'],
    [500, 'Something went wrong'],
    [503, 'Back in a moment'],
]);
