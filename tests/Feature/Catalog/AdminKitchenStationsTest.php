<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\KitchenStation as KitchenStationModel;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;

beforeEach(function (): void {
    $this->actingAs(User::factory()->admin()->create());
});

it('lists the stations in sort order with their category count', function (): void {
    $wok = KitchenStationModel::factory()->create(['name' => 'Wok', 'sort' => 20]);
    KitchenStationModel::factory()->create(['name' => 'Tandoor', 'sort' => 10]);
    CategoryModel::factory()->for($wok)->create();

    $this->get('/admin/kitchen-stations')
        ->assertOk()
        ->assertSeeInOrder(['Tandoor', 'Wok'])
        ->assertSee('1 category');
});

it('creates a station at the end of the list', function (): void {
    KitchenStationModel::factory()->create(['sort' => 30]);

    $this->post('/admin/kitchen-stations', ['name' => 'Fryer'])
        ->assertRedirect('/admin/kitchen-stations')
        ->assertSessionHas('success');

    expect(KitchenStationModel::query()->where('name', 'Fryer')->firstOrFail()->sort)->toBe(40);
});

it('requires a name of at most 100 characters', function (): void {
    $this->post('/admin/kitchen-stations', ['name' => ''])->assertSessionHasErrors('name');
    $this->post('/admin/kitchen-stations', ['name' => str_repeat('x', 101)])->assertSessionHasErrors('name');

    expect(KitchenStationModel::query()->count())->toBe(0);
});

it('shows the edit form and saves the changes', function (): void {
    $station = KitchenStationModel::factory()->create(['name' => 'Oven']);

    $this->get("/admin/kitchen-stations/{$station->id}/edit")->assertOk()->assertSee('Edit Oven');

    $this->put("/admin/kitchen-stations/{$station->id}", ['name' => 'Pizza Oven', 'is_active' => '0'])
        ->assertRedirect('/admin/kitchen-stations');

    expect($station->fresh()->name)->toBe('Pizza Oven')
        ->and($station->fresh()->is_active)->toBeFalse();
});

it('moves an unused station to the trash and restores it', function (): void {
    $station = KitchenStationModel::factory()->create();

    $this->delete("/admin/kitchen-stations/{$station->id}")->assertSessionHas('success');
    $this->assertSoftDeleted('kitchen_stations', ['id' => $station->id]);

    $this->post("/admin/kitchen-stations/{$station->id}/restore")->assertSessionHas('success');
    $this->assertNotSoftDeleted('kitchen_stations', ['id' => $station->id]);
});

it('refuses to delete a station that a category uses', function (): void {
    $station = KitchenStationModel::factory()->create();
    CategoryModel::factory()->for($station)->create();

    $this->delete("/admin/kitchen-stations/{$station->id}")->assertSessionHas('error');

    $this->assertNotSoftDeleted('kitchen_stations', ['id' => $station->id]);
});

it('saves a dragged order', function (): void {
    $oven = KitchenStationModel::factory()->create(['sort' => 10]);
    $fryer = KitchenStationModel::factory()->create(['sort' => 20]);

    $this->postJson('/admin/kitchen-stations/reorder', ['ids' => [$fryer->id, $oven->id]])->assertNoContent();

    expect($fryer->fresh()->sort)->toBe(10)
        ->and($oven->fresh()->sort)->toBe(20);
});

it('rejects a dragged order with an id twice', function (): void {
    $oven = KitchenStationModel::factory()->create();

    $this->postJson('/admin/kitchen-stations/reorder', ['ids' => [$oven->id, $oven->id]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('ids.0');
});

it('answers 404 for an unknown or deleted station', function (): void {
    $deleted = KitchenStationModel::factory()->create();
    $deleted->delete();

    $this->get('/admin/kitchen-stations/424242/edit')->assertNotFound();
    $this->get("/admin/kitchen-stations/{$deleted->id}/edit")->assertNotFound();
    $this->put('/admin/kitchen-stations/424242', ['name' => 'Oven'])->assertNotFound();
    $this->delete('/admin/kitchen-stations/424242')->assertNotFound();
});

it('does not let staff change stations', function (): void {
    $this->actingAs(User::factory()->staff()->create());

    $this->post('/admin/kitchen-stations', ['name' => 'Fryer'])->assertForbidden();

    expect(KitchenStationModel::query()->count())->toBe(0);
});
