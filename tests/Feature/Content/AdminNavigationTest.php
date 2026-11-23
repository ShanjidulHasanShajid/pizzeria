<?php

declare(strict_types=1);

use App\Modules\Content\Domain\Enums\NavRoute;
use App\Modules\Content\Domain\Repositories\NavItemRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedNavItemRepository;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::forget(CachedNavItemRepository::CACHE_KEY);
    $this->actingAs(User::factory()->admin()->create());
    $this->item = fn (NavRoute $route) => app(NavItemRepository::class)->get($route);
});

it('lists every page with its address', function (): void {
    $this->get('/admin/navigation')->assertOk()
        ->assertSeeInOrder(['Home', 'Menu', 'Customized Pizza'])
        ->assertSee('/build-your-pizza')
        ->assertSee('Track Order');
});

it('saves a label and shows the error only in the row that was sent', function (): void {
    $this->put('/admin/navigation/menu', ['nav_item' => 'menu', 'label' => 'Our Menu'])
        ->assertRedirect('/admin/navigation')->assertSessionHas('success');
    expect(($this->item)(NavRoute::Menu)->label)->toBe('Our Menu');

    $this->from('/admin/navigation')->put('/admin/navigation/menu', ['nav_item' => 'menu', 'label' => ''])->assertSessionHasErrors('label');
    $page = $this->get('/admin/navigation')->assertSee('id="label-menu-error"', false)->assertDontSee('id="label-home-error"', false);

    expect(substr_count((string) $page->getContent(), 'Enter a label.'))->toBe(1)
        ->and(($this->item)(NavRoute::Menu)->label)->toBe('Our Menu');
});

it('switches visibility and highlight', function (): void {
    $this->patch('/admin/navigation/faq/visibility')->assertRedirect('/admin/navigation')->assertSessionHas('success');
    $this->patch('/admin/navigation/faq/highlight')->assertRedirect('/admin/navigation');

    expect(($this->item)(NavRoute::Faq)->isVisible)->toBeTrue()
        ->and(($this->item)(NavRoute::Faq)->isHighlighted)->toBeTrue();
});

it('saves a dragged order and refuses an incomplete or unknown one', function (): void {
    $keys = array_map(fn (NavRoute $route): string => $route->value, NavRoute::cases());

    $this->postJson('/admin/navigation/reorder', ['ids' => array_reverse($keys)])->assertNoContent();
    expect(($this->item)(NavRoute::TrackOrder)->sort->value())->toBe(10);

    $this->postJson('/admin/navigation/reorder', ['ids' => ['menu', 'home']])->assertJsonValidationErrors('ids');
    $this->postJson('/admin/navigation/reorder', ['ids' => ['https://evil.example']])->assertJsonValidationErrors('ids.0');
    expect(($this->item)(NavRoute::TrackOrder)->sort->value())->toBe(10);
});

it('has no page for other words and refuses staff', function (): void {
    $this->put('/admin/navigation/nonsense', ['label' => 'x'])->assertNotFound();
    $this->patch('/admin/navigation/nonsense/visibility')->assertNotFound();

    $this->actingAs(User::factory()->staff()->create());
    $this->put('/admin/navigation/menu', ['label' => 'x'])->assertForbidden();
    $this->patch('/admin/navigation/menu/visibility')->assertForbidden();
    $this->patch('/admin/navigation/menu/highlight')->assertForbidden();
    $this->postJson('/admin/navigation/reorder', ['ids' => ['menu']])->assertForbidden();

    $this->assertDatabaseCount('nav_items', 0);
});
