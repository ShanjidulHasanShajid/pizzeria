<?php

declare(strict_types=1);

use App\Modules\Content\Domain\Enums\Weekday;
use App\Modules\Content\Domain\Repositories\LocationRepository;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedLocationRepository;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Shared\Application\Ports\Clock;
use App\Modules\Shared\Infrastructure\Adapters\FrozenClock;
use Illuminate\Support\Facades\Cache;

/**
 * The form as the browser sends it: open every day but Friday.
 *
 * @param  array<string, mixed>  $changes
 * @return array<string, mixed>
 */
function locationFields(array $changes = []): array
{
    $hours = [];

    foreach (Weekday::cases() as $day) {
        $hours[$day->value] = ['is_open' => $day === Weekday::Friday ? '0' : '1', 'opens' => '11:00', 'closes' => '23:00'];
    }

    return [
        'name' => 'Dhanmondi', 'address' => 'Road 27', 'area' => 'Dhanmondi', 'phone' => '01712 345678',
        'map_embed_url' => '', 'latitude' => '', 'longitude' => '', 'hours' => $hours,
        'is_pickup_available' => '1', 'is_active' => '1', ...$changes,
    ];
}

beforeEach(function (): void {
    Cache::forget(CachedLocationRepository::CACHE_KEY);
    $this->actingAs(User::factory()->admin()->create());
    $this->locations = app(LocationRepository::class);
});

it('creates and edits a location with its hours', function (): void {
    $this->get('/admin/locations')->assertOk()->assertSee('No locations yet');
    $this->get('/admin/locations/create')->assertOk()->assertSee('Copy to all days');

    $this->post('/admin/locations', locationFields([
        'map_embed_url' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18" width="600"></iframe>', 'latitude' => '23.7465', 'longitude' => '90.3760',
    ]))->assertRedirect('/admin/locations')->assertSessionHas('success');

    $location = $this->locations->all()[0];

    expect($location->details->mapEmbedUrl)->toBe('https://www.google.com/maps/embed?pb=!1m18')
        ->and($location->details->coordinates->longitude)->toBe('90.376')
        ->and($location->hours->toWeek()['friday'])->toBeNull()
        ->and($location->hours->toWeek()['saturday'])->toBe(['11:00', '23:00']);

    $this->get("/admin/locations/{$location->id}/edit")->assertOk()->assertSee('value="23.7465"', false);
    $this->put("/admin/locations/{$location->id}", locationFields(['name' => 'Gulshan', 'is_pickup_available' => '0']))
        ->assertRedirect('/admin/locations');

    expect($this->locations->find($location->id)->details->name)->toBe('Gulshan')
        ->and($this->locations->find($location->id)->details->isPickupAvailable)->toBeFalse()
        ->and($this->locations->find($location->id)->details->mapEmbedUrl)->toBe('');
});

it('shows every error under its field and keeps what was typed', function (): void {
    $fields = locationFields(['name' => '', 'phone' => 'call us', 'map_embed_url' => 'https://evil.example', 'latitude' => '23.7']);
    $fields['hours']['monday']['closes'] = '';

    $this->from('/admin/locations/create')->post('/admin/locations', $fields)
        ->assertRedirect('/admin/locations/create')
        ->assertSessionHasErrors(['name', 'phone', 'map_embed_url', 'latitude', 'hours.monday']);
    $this->get('/admin/locations/create')->assertSee('Road 27')->assertSee('Enter both times');

    $this->assertDatabaseCount('locations', 0);
});

it('shows whether a branch is open now', function (): void {
    $this->post('/admin/locations', locationFields());

    $this->app->instance(Clock::class, FrozenClock::at('2026-11-23 12:00')); // Monday
    $this->get('/admin/locations')->assertSee('Open now');
    $this->app->instance(Clock::class, FrozenClock::at('2026-11-27 12:00')); // Friday
    $this->get('/admin/locations')->assertSee('Closed now');
});

it('switches, deletes, restores and reorders locations', function (): void {
    $this->post('/admin/locations', locationFields(['name' => 'First']));
    $this->post('/admin/locations', locationFields(['name' => 'Second']));
    [$first, $second] = $this->locations->all();

    $this->patch("/admin/locations/{$first->id}/toggle")->assertSessionHas('success');
    expect($this->locations->find($first->id)->isActive)->toBeFalse();

    $this->postJson('/admin/locations/reorder', ['ids' => [$second->id, $first->id]])->assertNoContent();
    $this->get('/admin/locations')->assertSeeInOrder(['Second', 'First']);

    $this->delete("/admin/locations/{$first->id}")->assertSessionHas('success');
    $this->assertSoftDeleted('locations', ['id' => $first->id]);
    $this->post("/admin/locations/{$first->id}/restore")->assertSessionHas('success');
    expect($this->locations->all())->toHaveCount(2);
});

it('answers 404 for unknown locations and refuses staff', function (): void {
    $this->get('/admin/locations/424242/edit')->assertNotFound();
    $this->put('/admin/locations/424242', locationFields())->assertNotFound();
    $this->patch('/admin/locations/424242/toggle')->assertNotFound();
    $this->delete('/admin/locations/424242')->assertNotFound();
    $this->post('/admin/locations/424242/restore')->assertNotFound();

    $this->actingAs(User::factory()->staff()->create());
    $this->post('/admin/locations', locationFields())->assertForbidden();
    $this->put('/admin/locations/1', locationFields())->assertForbidden();
    $this->patch('/admin/locations/1/toggle')->assertForbidden();
    $this->delete('/admin/locations/1')->assertForbidden();
    $this->post('/admin/locations/1/restore')->assertForbidden();
    $this->postJson('/admin/locations/reorder', ['ids' => [1]])->assertForbidden();

    $this->assertDatabaseCount('locations', 0);
});
