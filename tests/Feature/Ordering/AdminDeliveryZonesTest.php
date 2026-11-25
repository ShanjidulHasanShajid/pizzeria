<?php

declare(strict_types=1);

use App\Modules\Content\Domain\Repositories\LocationRepository;
use App\Modules\Content\Domain\ValueObjects\Location;
use App\Modules\Content\Domain\ValueObjects\LocationDetails;
use App\Modules\Content\Domain\ValueObjects\OpeningHours;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedLocationRepository;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Ordering\Application\Queries\AdminDeliveryZoneQuery;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::forget(CachedLocationRepository::CACHE_KEY);
    $this->actingAs(User::factory()->admin()->create());
    $this->locations = app(LocationRepository::class);
    $this->query = app(AdminDeliveryZoneQuery::class);
    $this->branch = fn (string $name = 'Gulshan branch'): int => $this->locations->save(Location::create(
        LocationDetails::fromInput($name, 'Road 27', '', '16247', '', '', '', true), OpeningHours::alwaysClosed(), true, SortOrder::first(),
    ));
    // The form as the browser sends it.
    $this->fields = fn (int $branch, array $changes = []): array => [
        'name' => 'Banani', 'location_id' => (string) $branch, 'fee' => '80.00', 'min_order' => '300', 'is_active' => '1', ...$changes,
    ];
});

it('asks for a location first', function (): void {
    $this->get('/admin/delivery-zones')->assertOk()->assertSee('No delivery zones yet');
    $this->get('/admin/delivery-zones/create')->assertOk()->assertSee('Add a location first');
});

it('creates a zone; the form starts with 80 taka', function (): void {
    $branch = ($this->branch)();

    $this->get('/admin/delivery-zones/create')->assertOk()->assertSee('value="80.00"', false)->assertSee('Gulshan branch');
    $this->post('/admin/delivery-zones', ($this->fields)($branch))->assertRedirect('/admin/delivery-zones')->assertSessionHas('success');

    $this->assertDatabaseHas('delivery_zones', ['name' => 'Banani', 'location_id' => $branch, 'fee' => 8000, 'min_order' => 30000, 'is_active' => true, 'sort' => 10]);
    $this->get('/admin/delivery-zones')->assertOk()->assertSee('From Gulshan branch')->assertSee('৳80')->assertSee('৳300');
});

it('edits a zone', function (): void {
    $first = ($this->branch)();
    $second = ($this->branch)('Dhanmondi branch');
    $this->post('/admin/delivery-zones', ($this->fields)($first));
    $id = $this->query->table()[0]->id;

    $this->get("/admin/delivery-zones/{$id}/edit")->assertOk()->assertSee('Edit Banani')->assertSee('value="300.00"', false);
    $this->put("/admin/delivery-zones/{$id}", ($this->fields)($second, ['name' => 'Mohammadpur', 'fee' => '0', 'min_order' => '', 'is_active' => '0']))
        ->assertRedirect('/admin/delivery-zones');

    $this->assertDatabaseHas('delivery_zones', ['id' => $id, 'name' => 'Mohammadpur', 'location_id' => $second, 'fee' => 0, 'min_order' => 0, 'is_active' => false]);
    $this->get('/admin/delivery-zones')->assertSee('Free delivery')->assertSee('No minimum')->assertSee('Switched off');
});

it('shows every error under its field and keeps what was typed', function (): void {
    ($this->branch)();

    $this->from('/admin/delivery-zones/create')
        ->post('/admin/delivery-zones', ['name' => '', 'location_id' => '424242', 'fee' => 'eighty', 'min_order' => '-5', 'is_active' => '1'])
        ->assertRedirect('/admin/delivery-zones/create')
        ->assertSessionHasErrors(['name', 'location_id', 'fee', 'min_order']);
    $this->get('/admin/delivery-zones/create')->assertSee('eighty')->assertSee('Choose a branch of the list.');

    $this->assertDatabaseCount('delivery_zones', 0);
});

it('refuses a form with missing or odd values', function (array $fields): void {
    $this->post('/admin/delivery-zones', $fields)->assertSessionHasErrors();

    $this->assertDatabaseCount('delivery_zones', 0);
})->with([
    'nothing' => [[]],
    'lists' => [['name' => ['x'], 'location_id' => ['1'], 'fee' => ['80'], 'min_order' => ['0']]],
    'a huge amount' => [['name' => 'Banani', 'location_id' => '1', 'fee' => '99999999999', 'min_order' => '']],
]);

it('switches, deletes, restores and reorders zones', function (): void {
    $branch = ($this->branch)();
    $this->post('/admin/delivery-zones', ($this->fields)($branch, ['name' => 'First zone']));
    $this->post('/admin/delivery-zones', ($this->fields)($branch, ['name' => 'Second zone']));
    [$first, $second] = $this->query->table();

    $this->patch("/admin/delivery-zones/{$first->id}/toggle")->assertSessionHas('success');
    expect($this->query->zone($first->id)->isActive)->toBeFalse();

    $this->postJson('/admin/delivery-zones/reorder', ['ids' => [$second->id, $first->id]])->assertNoContent();
    $this->get('/admin/delivery-zones')->assertSeeInOrder(['Second zone', 'First zone']);

    $this->delete("/admin/delivery-zones/{$first->id}")->assertSessionHas('success');
    $this->assertSoftDeleted('delivery_zones', ['id' => $first->id]);

    $this->get('/admin/trash?type=delivery_zone')->assertOk()->assertSee('Delivery zones (1)')->assertSee('First zone');
    $this->post("/admin/delivery-zones/{$first->id}/restore")->assertSessionHas('success');
    expect($this->query->table())->toHaveCount(2);
});

it('warns about a zone whose branch was switched off or deleted', function (): void {
    $branch = ($this->branch)();
    $this->post('/admin/delivery-zones', ($this->fields)($branch));
    $id = $this->query->table()[0]->id;

    $this->patch("/admin/locations/{$branch}/toggle");
    $this->get('/admin/delivery-zones')->assertOk()->assertSee('Branch switched off');

    $this->delete("/admin/locations/{$branch}");
    $this->get('/admin/delivery-zones')->assertOk()->assertSee('Branch deleted');
    $this->get("/admin/delivery-zones/{$id}/edit")->assertOk()->assertSee('The branch of this zone was deleted');
    $this->put("/admin/delivery-zones/{$id}", ($this->fields)($branch))->assertSessionHasErrors('location_id');
});

it('lets only a super admin delete a zone forever', function (): void {
    $this->post('/admin/delivery-zones', ($this->fields)(($this->branch)()));
    $id = $this->query->table()[0]->id;
    $this->delete("/admin/delivery-zones/{$id}");

    $this->delete("/admin/delivery-zones/{$id}/force")->assertForbidden();

    $this->actingAs(User::factory()->superAdmin()->create());
    $this->delete("/admin/delivery-zones/{$id}/force")->assertSessionHas('success');
    $this->delete("/admin/delivery-zones/{$id}/force")->assertNotFound();
    $this->assertDatabaseCount('delivery_zones', 0);
});

it('does not delete a location forever while a zone belongs to it, also one in the Trash', function (): void {
    $branch = ($this->branch)();
    $this->post('/admin/delivery-zones', ($this->fields)($branch));
    $id = $this->query->table()[0]->id;
    $this->delete("/admin/delivery-zones/{$id}");
    $this->delete("/admin/locations/{$branch}");
    $this->actingAs(User::factory()->superAdmin()->create());

    $this->delete("/admin/locations/{$branch}/force")->assertRedirect()->assertSessionHas('error');
    $this->assertSoftDeleted('locations', ['id' => $branch]);

    $this->delete("/admin/delivery-zones/{$id}/force");
    $this->delete("/admin/locations/{$branch}/force")->assertSessionHas('success');
    $this->assertDatabaseMissing('locations', ['id' => $branch]);
});

it('answers 404 for unknown zones and refuses staff', function (): void {
    $fields = ($this->fields)(($this->branch)());

    $this->get('/admin/delivery-zones/424242/edit')->assertNotFound();
    $this->put('/admin/delivery-zones/424242', $fields)->assertNotFound();
    $this->patch('/admin/delivery-zones/424242/toggle')->assertNotFound();
    $this->delete('/admin/delivery-zones/424242')->assertNotFound();
    $this->post('/admin/delivery-zones/424242/restore')->assertNotFound();

    $this->actingAs(User::factory()->staff()->create());
    $this->post('/admin/delivery-zones', $fields)->assertForbidden();
    $this->put('/admin/delivery-zones/1', $fields)->assertForbidden();
    $this->patch('/admin/delivery-zones/1/toggle')->assertForbidden();
    $this->delete('/admin/delivery-zones/1')->assertForbidden();
    $this->post('/admin/delivery-zones/1/restore')->assertForbidden();
    $this->postJson('/admin/delivery-zones/reorder', ['ids' => [1]])->assertForbidden();

    $this->assertDatabaseCount('delivery_zones', 0);
});
