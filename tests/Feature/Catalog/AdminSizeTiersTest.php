<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

beforeEach(function (): void {
    $this->actingAs(User::factory()->admin()->create());
});

it('lists the sizes in sort order with their variant count', function (): void {
    $jumbo = SizeTierModel::factory()->create(['name' => 'Jumbo', 'sort' => 20]);
    SizeTierModel::factory()->create(['name' => 'Mini', 'sort' => 10]);
    ProductVariantModel::factory()->count(2)->forSizeTier($jumbo)->sequence(['label' => 'Jumbo A'], ['label' => 'Jumbo B'])->create();

    $this->get('/admin/size-tiers')
        ->assertOk()
        ->assertSeeInOrder(['Mini', 'Jumbo'])
        ->assertSee('2 variants');
});

it('creates a size with a slug made from the name', function (): void {
    $this->post('/admin/size-tiers', ['name' => 'Extra Large', 'slug' => ''])
        ->assertRedirect('/admin/size-tiers');

    expect(SizeTierModel::query()->where('name', 'Extra Large')->firstOrFail()->slug)->toBe('extra-large');
});

it('shows an error under the slug when the typed slug is taken, also by a size in the trash', function (): void {
    SizeTierModel::factory()->create(['slug' => 'medium'])->delete();

    $this->post('/admin/size-tiers', ['name' => 'Medium', 'slug' => 'medium'])->assertSessionHasErrors('slug');
    $this->post('/admin/size-tiers', ['name' => 'Medium', 'slug' => 'Not A Slug'])->assertSessionHasErrors('slug');

    expect(SizeTierModel::query()->count())->toBe(0);
});

it('keeps the slug when it is left empty on the edit form', function (): void {
    $size = SizeTierModel::factory()->create(['name' => 'Medium', 'slug' => 'medium']);

    $this->get("/admin/size-tiers/{$size->id}/edit")->assertOk()->assertSee('Edit Medium');

    $this->put("/admin/size-tiers/{$size->id}", ['name' => 'Regular', 'slug' => '', 'is_active' => '1'])
        ->assertRedirect('/admin/size-tiers');

    expect($size->fresh()->name)->toBe('Regular')
        ->and($size->fresh()->slug)->toBe('medium');
});

it('switches a size off and on from the list', function (): void {
    $size = SizeTierModel::factory()->create();

    $this->patch("/admin/size-tiers/{$size->id}/toggle")->assertSessionHas('success');
    expect($size->fresh()->is_active)->toBeFalse();

    $this->patch("/admin/size-tiers/{$size->id}/toggle");
    expect($size->fresh()->is_active)->toBeTrue();
});

it('refuses to delete a size that a variant uses', function (): void {
    $size = SizeTierModel::factory()->create();
    ProductVariantModel::factory()->forSizeTier($size)->create();

    $this->delete("/admin/size-tiers/{$size->id}")->assertSessionHas('error');

    $this->assertNotSoftDeleted('size_tiers', ['id' => $size->id]);
});

it('moves an unused size to the trash and restores it', function (): void {
    $size = SizeTierModel::factory()->create();

    $this->delete("/admin/size-tiers/{$size->id}")->assertSessionHas('success');
    $this->assertSoftDeleted('size_tiers', ['id' => $size->id]);

    $this->post("/admin/size-tiers/{$size->id}/restore")->assertSessionHas('success');
    $this->assertNotSoftDeleted('size_tiers', ['id' => $size->id]);
});

it('saves a dragged order', function (): void {
    $personal = SizeTierModel::factory()->create(['sort' => 10]);
    $large = SizeTierModel::factory()->create(['sort' => 20]);

    $this->postJson('/admin/size-tiers/reorder', ['ids' => [$large->id, $personal->id]])->assertNoContent();

    expect($large->fresh()->sort)->toBe(10)
        ->and($personal->fresh()->sort)->toBe(20);
});

it('does not let staff change sizes', function (): void {
    $size = SizeTierModel::factory()->create();
    $this->actingAs(User::factory()->staff()->create());

    $this->patch("/admin/size-tiers/{$size->id}/toggle")->assertForbidden();

    expect($size->fresh()->is_active)->toBeTrue();
});

it('refuses to delete a size that option prices use, also forever', function (): void {
    $size = SizeTierModel::factory()->create();
    $groups = app(OptionGroupRepository::class);
    $crust = OptionGroup::create('Crust', 'Crust', SortOrder::first());
    $crust->addOption(Option::withPricesPerSize('Stuffed', [$size->id => Money::fromTaka('150')], SortOrder::first()));
    $id = $groups->save($crust);

    $this->delete("/admin/size-tiers/{$size->id}")->assertSessionHas('error');
    $this->assertNotSoftDeleted('size_tiers', ['id' => $size->id]);

    $saved = $groups->find($id);
    $saved->removeOption($saved->options()[0]->id());
    $groups->save($saved);

    $this->delete("/admin/size-tiers/{$size->id}")->assertSessionHas('success');
    $this->actingAs(User::factory()->superAdmin()->create());
    $this->delete("/admin/size-tiers/{$size->id}/force")->assertSessionHas('error');
    $this->assertSoftDeleted('size_tiers', ['id' => $size->id]);
});
