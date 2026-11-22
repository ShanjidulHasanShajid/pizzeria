<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Enums\TagType;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;

beforeEach(function (): void {
    $this->actingAs(User::factory()->admin()->create());
});

it('lists the tags in sort order with type and product count', function (): void {
    $zesty = TagModel::factory()->create(['name' => 'Zesty', 'type' => TagType::Flavour, 'sort' => 20]);
    TagModel::factory()->create(['name' => 'Vegan', 'type' => TagType::Dietary, 'sort' => 10]);
    ProductModel::factory()->create()->tags()->attach($zesty);

    $this->get('/admin/tags')
        ->assertOk()
        ->assertSeeInOrder(['Vegan', 'Dietary', 'Zesty', 'Flavour'])
        ->assertSee('1 product');
});

it('creates a tag with a type, an icon and a slug made from the name', function (): void {
    $this->post('/admin/tags', ['name' => 'Contains Nuts', 'slug' => '', 'type' => 'allergen', 'icon' => 'exclamation-triangle'])
        ->assertRedirect('/admin/tags');

    $tag = TagModel::query()->where('name', 'Contains Nuts')->firstOrFail();

    expect($tag->slug)->toBe('contains-nuts')
        ->and($tag->type)->toBe(TagType::Allergen)
        ->and($tag->icon)->toBe('exclamation-triangle');
});

it('accepts only the offered types and icons', function (): void {
    $this->post('/admin/tags', ['name' => 'Odd', 'type' => 'colour', 'icon' => ''])->assertSessionHasErrors('type');
    $this->post('/admin/tags', ['name' => 'Odd', 'type' => 'flavour', 'icon' => 'rocket'])->assertSessionHasErrors('icon');

    expect(TagModel::query()->count())->toBe(0);
});

it('shows the edit form and saves the changes', function (): void {
    $tag = TagModel::factory()->create(['name' => 'Hot', 'slug' => 'hot', 'type' => TagType::Flavour, 'icon' => 'fire']);

    $this->get("/admin/tags/{$tag->id}/edit")->assertOk()->assertSee('Edit Hot');

    $this->put("/admin/tags/{$tag->id}", ['name' => 'Spicy', 'slug' => '', 'type' => 'flavour', 'icon' => '', 'is_active' => '0'])
        ->assertRedirect('/admin/tags');

    expect($tag->fresh()->name)->toBe('Spicy')
        ->and($tag->fresh()->slug)->toBe('hot')
        ->and($tag->fresh()->icon)->toBeNull()
        ->and($tag->fresh()->is_active)->toBeFalse();
});

it('moves a tag that products use to the trash and keeps the link', function (): void {
    $tag = TagModel::factory()->create();
    $product = ProductModel::factory()->create();
    $product->tags()->attach($tag);

    $this->delete("/admin/tags/{$tag->id}")->assertSessionHas('success');

    $this->assertSoftDeleted('tags', ['id' => $tag->id]);
    $this->assertDatabaseHas('product_tag', ['product_id' => $product->id, 'tag_id' => $tag->id]);

    $this->post("/admin/tags/{$tag->id}/restore")->assertSessionHas('success');
    $this->assertNotSoftDeleted('tags', ['id' => $tag->id]);
});

it('saves a dragged order', function (): void {
    $veg = TagModel::factory()->create(['sort' => 10]);
    $spicy = TagModel::factory()->create(['sort' => 20]);

    $this->postJson('/admin/tags/reorder', ['ids' => [$spicy->id, $veg->id]])->assertNoContent();

    expect($spicy->fresh()->sort)->toBe(10)
        ->and($veg->fresh()->sort)->toBe(20);
});

it('does not let staff change tags', function (): void {
    $tag = TagModel::factory()->create();
    $this->actingAs(User::factory()->staff()->create());

    $this->delete("/admin/tags/{$tag->id}")->assertForbidden();

    $this->assertNotSoftDeleted('tags', ['id' => $tag->id]);
});
